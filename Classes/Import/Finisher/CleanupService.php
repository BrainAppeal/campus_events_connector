<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Finisher;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Repository\AbstractImportRowRepository;
use BrainAppeal\CampusEventsConnector\Import\Repository\FileReferenceRepository;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Provides methods for cleaning up database tables and resetting related data.
 * Mainly used to truncate import-related tables and manage file references.
 */
readonly class CleanupService
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected FileReferenceRepository $fileReferenceRepository,
        protected ConnectionPool $connectionPool
    ) {}

    /**
     * Truncates specified database tables. By default, truncates only the import-related
     * tables. If the $clearAllTables parameter is set to true, it will additionally truncate
     * all tables associated with the registered data transformers.
     *
     * @param bool $clearAllTables If true, truncates all registered tables in addition to the
     *                             default import tables.
     * @param bool $resetDataHashes
     * @param string $dataSource
     * @return array Returns the list of tables that were truncated.
     * @throws Exception
     */
    public function truncateTables(bool $clearAllTables, bool $resetDataHashes, string $dataSource): array
    {
        $connection = $this->getDatabaseConnection();
        $tables = [AbstractImportRowRepository::TABLE_IMPORT_ROW, ImportEntryManager::TABLE_IMPORT];
        $fileReferenceTables = [];
        if ($clearAllTables) {
            foreach ($this->dataTransformerFactory->getDataTransformersForImportGroup($dataSource) as $dataTransformer) {
                $tables[] = $dataTransformer->getTable();
                foreach ($dataTransformer->getImportConfiguration()->getImportFieldMap() as $mapEntry) {
                    if ($mapEntry->isReference() && $mmTable = $mapEntry->getManyToManyTable()) {
                        $tables[] = $mmTable;
                    }
                }
                if ($dataTransformer->hasFileTransformations()) {
                    $fileReferenceTables[] = $dataTransformer->getTable();
                }
            }
        } elseif ($resetDataHashes) {
            // Reset all hashes to force update of imported records
            foreach ($this->dataTransformerFactory->getTableNamesForImportGroup($dataSource) as $table) {
                $connection->executeStatement(sprintf("UPDATE %s SET data_hash = ''", $table));
            }
        }
        foreach ($tables as $table) {
            $connection->executeStatement('TRUNCATE TABLE ' . $table);
        }
        // Clear file references after truncating tables
        if (!empty($fileReferenceTables)) {
            $this->fileReferenceRepository->cleanupFileReferences($fileReferenceTables);
        }
        return $tables;
    }

    /**
     * Deletes import entries for the specified data source.
     *
     * @param string $dataSource The identifier of the data source whose import entries should be deleted.
     */
    public function deleteImportEntriesForDataSource(string $dataSource): void
    {
        $table = ImportEntryManager::TABLE_IMPORT;
        $this->cleanUpOldRecords('-1 month', null, $table);
        $connectionPool = $this->connectionPool;
        // Stop all running imports for the given data source
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->update($table);
        $queryBuilder->set('running', 0);
        $queryBuilder->set('hidden', 1);
        $queryBuilder->set('import_end', time());
        $queryBuilder->where(
            $queryBuilder->expr()->eq(
                'import_source',
                $queryBuilder->createNamedParameter($dataSource, Connection::PARAM_STR)
            ),
            $queryBuilder->expr()->eq(
                'hidden',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ),
        )->executeStatement();
        $this->deleteOldImportRows($dataSource);
    }

    /**
     * Mark old records as deleted that were imported completely
     * @param string $datetime The date time string to parse
     * @param ?string $dataSource The identifier of the data source whose import entries should be deleted.
     * @param string $table
     */
    public function cleanUpOldRecords(string $datetime = '-1 week', ?string $dataSource = null, string $table = AbstractImportRowRepository::TABLE_IMPORT_ROW): void
    {
        $connectionPool = $this->connectionPool;
        $clearOldQueryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $clearOldQueryBuilder->delete($table)
            ->where(
                $clearOldQueryBuilder->expr()->lt(
                    'tstamp',
                    $clearOldQueryBuilder->createNamedParameter((int)strtotime($datetime), Connection::PARAM_INT)
                ),
            );
        if ($dataSource !== null && $table === AbstractImportRowRepository::TABLE_IMPORT_ROW) {
            $clearOldQueryBuilder
                ->andWhere(
                    $clearOldQueryBuilder->expr()->lt(
                        'source_type',
                        $clearOldQueryBuilder->createNamedParameter($dataSource, Connection::PARAM_STR)
                    ),
                );
        }
        if ($dataSource !== null && $table === ImportEntryManager::TABLE_IMPORT) {
            $clearOldQueryBuilder
                ->andWhere(
                    $clearOldQueryBuilder->expr()->lt(
                        'import_source',
                        $clearOldQueryBuilder->createNamedParameter($dataSource, Connection::PARAM_STR)
                    ),
                );
        }
        $clearOldQueryBuilder->executeStatement();
    }

    /**
     * Deletes old and obsolete import rows from the database that are no longer needed
     * and clears outdated references from previous imports to maintain data integrity.
     *
     * @param string $groupKey
     * @param int|null $importId The ID of the current import process
     * @throws Exception
     */
    public function deleteOldImportRows(string $groupKey, ?int $importId = null): void
    {
        $importEntryTable = ImportEntryManager::TABLE_IMPORT;
        $connectionPool = $this->connectionPool;
        $queryBuilder = $connectionPool->getQueryBuilderForTable($importEntryTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('uid')->from($importEntryTable)
            ->where($queryBuilder->expr()->eq('import_source', $queryBuilder->createNamedParameter($groupKey)))
            ->orderBy('uid', 'ASC');
        if ($importId !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->lt('uid', $queryBuilder->createNamedParameter($importId)));
        }
        $uidList = $queryBuilder->executeQuery()->fetchFirstColumn();
        if (empty($uidList)) {
            return;
        }
        $this->deleteImportRows($uidList);
        if (count($uidList) > 5) {
            $firstFiveUids = array_slice($uidList, 0, count($uidList) - 5);
            $cleanImportQueryBuilder = $connectionPool->getQueryBuilderForTable($importEntryTable);
            $cleanImportQueryBuilder->getRestrictions()->removeAll();
            $cleanImportQueryBuilder->delete($importEntryTable)
                ->where($cleanImportQueryBuilder->expr()->in('uid', $cleanImportQueryBuilder->createNamedParameter($firstFiveUids, Connection::PARAM_INT_ARRAY)));
            $cleanImportQueryBuilder->executeStatement();
        }
    }

    /**
     * Delete all import rows with the given import IDs
     *
     * @param int[] $importIds
     */
    public function deleteImportRows(array $importIds): void
    {
        $table = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        $connectionPool = $this->connectionPool;
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->delete($table);
        if ($importIds) {
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    'import_id',
                    $queryBuilder->createNamedParameter($importIds, Connection::PARAM_INT_ARRAY)
                )
            );
        }
        $queryBuilder->executeStatement();
    }

    /**
     * @param string $table
     * @return Connection
     */
    protected function getDatabaseConnection(string $table = AbstractImportRowRepository::TABLE_IMPORT_ROW): Connection
    {
        return $this->connectionPool->getConnectionForTable($table);
    }
}
