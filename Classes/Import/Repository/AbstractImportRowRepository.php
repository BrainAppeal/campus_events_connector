<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Handles the persistence of import rows into the database.
 */
abstract readonly class AbstractImportRowRepository
{
    public const TABLE_IMPORT_ROW = 'tx_campuseventsconnector_import_row';

    public function __construct(protected LoggerInterface $logger, protected ConnectionPool $connectionPool) {}

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
     * Creates an ImportRecordModel instance from the given import row.
     *
     * @param array<string, mixed> $row Array representing a database row containing data required to create an ImportRecordModel.
     * @return ImportRecordModel The populated ImportRecordModel instance created from the given row.
     */
    final protected function createImportRecordModelFromRow(array $row): ImportRecordModel
    {
        $typedIdentifier = !empty($row['source_record_uid']) ? (int)$row['source_record_uid'] : (string)$row['source_record_identifier'];
        $model = new ImportRecordModel(
            (string)$row['import_data'],
            (string)$row['source_type'],
            $typedIdentifier,
            (string)$row['data_hash'],
        );
        $model->updateFromArray($row);
        return $model;
    }

    /**
     * Finds and retrieves rows from the import row table that match specific conditions
     * related to the provided import entry.
     *
     * @param int $importId The import entry object used to filter the rows based on its unique identifier.
     * @param string|null $targetTable Optional import table name to filter the rows.
     * @return array<ImportRecordModel> An array of ImportRecordModel objects representing the matching rows
     *               with the required conditions.
     * @throws Exception
     */
    final public function findIncompleteImportRows(int $importId, ?string $targetTable = null): array
    {
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId);
        $queryBuilder->andWhere(
            // Only process records where the full data have been loaded
            $queryBuilder->expr()->eq('data_fully_loaded', $queryBuilder->createNamedParameter(
                0,
                Connection::PARAM_INT
            ))
        );
        if ($targetTable) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    'source_type',
                    $queryBuilder->createNamedParameter($targetTable)
                )
            );
        }
        $queryBuilder->orderBy('sys_language_uid', 'ASC');
        $queryBuilder->addOrderBy('priority', 'DESC');
        $queryBuilder->addOrderBy('source_record_uid', 'ASC');
        return $this->getImportRowsWithQueryBuilder($queryBuilder);
    }

    /**
     * Retrieves import rows using the given QueryBuilder instance.
     *
     * @param QueryBuilder $queryBuilder The query builder instance used to execute the query and fetch rows.
     * @return array An array of import record models containing data from the executed query.
     * @throws Exception
     */
    final protected function getImportRowsWithQueryBuilder(QueryBuilder $queryBuilder): array
    {
        $importResult = $queryBuilder->executeQuery();
        $importRows = [];
        $emptyRows = [];
        while ($row = $importResult->fetchAssociative()) {
            $model = $this->createImportRecordModelFromRow($row);
            if (!$model->hasData()) {
                $emptyRows[] = $model;
            } else {
                $importRows[] = $model;
            }
        }
        $importResult->free();
        if (!empty($emptyRows)) {
            $this->markRowsAsFinished(null, $emptyRows);
        }
        return $importRows;
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
        $connectionPool = $this->connectionPool;
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
                    $queryBuilder->createNamedParameter($importRowUidList, Connection::PARAM_INT_ARRAY)
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
    protected function createQueryBuilderForImportRowTable(int $importId, bool $includeSkipped = false): QueryBuilder
    {
        $table = self::TABLE_IMPORT_ROW;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('import_id', $queryBuilder->createNamedParameter(
                    $importId,
                    Connection::PARAM_INT
                )),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                )),
            );
        if (!$includeSkipped) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('import_skipped', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                ))
            );
        }
        return $queryBuilder;
    }

    /**
     * @param string $table
     * @return Connection
     */
    protected function getDatabaseConnection(string $table = self::TABLE_IMPORT_ROW): Connection
    {
        return $this->connectionPool->getConnectionForTable($table);
    }
}
