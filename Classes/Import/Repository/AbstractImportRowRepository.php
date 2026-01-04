<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use Doctrine\DBAL\ArrayParameterType;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the persistence of import rows into the database.
 */
abstract readonly class AbstractImportRowRepository
{
    public const TABLE_IMPORT_ROW = 'tx_campuseventsconnector_import_row';

    /**
     * Mark the import rows as finished. If no rows are given, all rows will be marked as finished
     *
     * @param int|null $importId
     * @param array<ImportRecordModel> $importedRows
     * @param bool $clearRawData
     */
    final protected function markRowsAsFinished(?int $importId, array $importedRows = [], bool $clearRawData = true): void
    {
        $queryBuilder = $this->createUpdateQueryBuilderForRecordTable($importId, $importedRows);
        $queryBuilder
            ->set('data_processed', 1)
            ->set('files_processed', 1);
        if ($clearRawData) {
            $queryBuilder->set('import_data', json_encode(['finished' => 1]));
        }
        $queryBuilder->executeStatement();
    }

    /**
     * Creates and configures a query builder instance for updating the record table.
     * The query builder is prepared to update the `tstamp` column in the `import_row`
     * table based on the provided `importId` and/or the list of imported rows.
     *
     * @param int|null $importId The ID of the import process to filter records for update.
     *                           If null, no specific import ID filter is applied.
     * @param array $importedRows An array of imported row objects. If provided, only records
     *                            corresponding to the `uid` values of these rows will be considered for update.
     * @return QueryBuilder A query builder instance configured for updating the `import_row` table.
     */
    protected function createUpdateQueryBuilderForRecordTable(?int $importId, array $importedRows = []): QueryBuilder
    {
        $table = self::TABLE_IMPORT_ROW;
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $now = time();
        $queryBuilder->update($table);
        if ($importId) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq(
                    'import_id',
                    $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
                )
            );
        }
        if (!empty($importedRows)) {
            $importRowUidList = [];
            foreach ($importedRows as $importedRow) {
                $importRowUidList[] = $importedRow->getUid();
            }
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($importRowUidList, ArrayParameterType::INTEGER)
                )
            );
        }
        $queryBuilder->set('tstamp', $now);
        return $queryBuilder;
    }

    /**
     * Finds and retrieves rows from the import row table that match specific conditions
     * related to the provided import entry and processing type.
     *
     * @param int $importId The id of the import entry
     * @return QueryBuilder An array of ImportRecordModel objects representing the matching rows
     *               with the required conditions.
     */
    protected function createQueryBuilderForImportRowTable(int $importId): QueryBuilder
    {
        $table = self::TABLE_IMPORT_ROW;
        /** @var QueryBuilder $queryBuilder */
        /** @noinspection NullPointerExceptionInspection */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('import_id', $queryBuilder->createNamedParameter(
                    $importId,
                    Connection::PARAM_INT
                )),
                $queryBuilder->expr()->eq('import_skipped', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                )),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                )),
            );
        return $queryBuilder;
    }

    /**
     * @param string $table
     * @return Connection
     */
    protected function getDatabaseConnection(string $table = self::TABLE_IMPORT_ROW): Connection
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($table);
    }
}
