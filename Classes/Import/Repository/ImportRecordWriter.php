<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use Doctrine\DBAL\Exception;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\HasLastUpdateFieldDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\ImportOptionsFactory;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the writing of import rows into the database.
 */
readonly class ImportRecordWriter extends AbstractImportRowRepository
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected ImportOptionsFactory   $importOptionsFactory,
        protected LoggerInterface        $logger
    ) {}

    protected function getImportOptions(): AbstractImportOptions
    {
        return $this->importOptionsFactory->get();
    }

    /**
     * Saves the rows to be imported in the database
     *
     * @param array<ImportRecordModel> $importRows
     */
    public function addRows(array $importRows): void
    {
        $importOptions = $this->getImportOptions();
        $importId = $importOptions->getImportId();
        $pid = $importOptions->getPid();
        $table = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        $connection = $this->getDatabaseConnection();
        $fields = null;
        $data = [];
        $insertOffset = 0;
        $importedTables = [];
        foreach ($importRows as $model) {
            if ($model->isInvalidated()) {
                continue;
            }
            $importedTables[$model->getTargetTable()] = true;
            $dbRow = $model->toArray();
            $dbRow['import_id'] = $importId;
            $dbRow['pid'] = $pid;
            $data[] = $dbRow;
            if (!$fields) {
                $fields = array_keys($dbRow);
            }
            ++$insertOffset;
            if ($insertOffset > 50) {
                $connection->bulkInsert($table, $data, $fields);
                $insertOffset = 0;
                $data = [];
            }
        }
        if ($insertOffset > 0) {
            $connection->bulkInsert($table, $data, $fields);
        }
        // Cleans up import rows that have an unchanged data hash from the current import entry
        if (!$importOptions->isForceUpdate()) {
            $this->markRowsToBeSkipped($importId, array_keys($importedTables));
        }
    }

    /**
     * Updates the import rows in the database with the given data.
     *
     * @param array<ImportRecordModel> $updatedRows Array of updated row models to be processed and stored in the database.
     */
    public function update(array $updatedRows): void
    {
        $table = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        $connection = $this->getDatabaseConnection();
        foreach ($updatedRows as $model) {
            $connection->update($table, $model->toArray(true), ['uid' => $model->getUid()]);
        }
    }

    /**
     * Persists additional import data by updating existing rows or persisting new rows based on the provided data.
     *
     * This method handles the processing of updated and new rows. If updated rows are present, they are processed and saved.
     * If new rows are provided, they are persisted based on the import entry. When no new rows are present, it updates the
     * target record IDs for the respective source types of the updated rows.
     *
     * @param array $updatedRows An array of updated rows to be processed and saved.
     * @param array $newRows An array of new rows to be persisted.
     */
    public function persistAdditionalImportData(array $updatedRows, array $newRows): void
    {
        if (!empty($updatedRows)) {
            $this->update($updatedRows);
        }
        if (!empty($newRows)) {
            $this->addRows($newRows);
        }
    }

    /**
     * Counts the number of rows that are missing required details based on the current import context.
     *
     * This method retrieves the import options, constructs a query tailored to identify rows with missing details,
     * and performs a count operation to determine the total number of such rows.
     *
     * @return int The number of rows that are missing details.
     */
    public function countRowsMissingDetails(): int
    {
        $queryBuilder = $this->createQueryBuilderForMissingDetails();
        return $queryBuilder
            ->count('*')
            ->executeQuery()->fetchOne();
    }

    /**
     * Finds and retrieves rows from the import row table that match specific conditions
     * related to the provided import entry and processing type.
     *
     * @param int $limit Optional. The maximum number of rows to retrieve. If set to 0, no limit is applied.
     * @return ImportRecordModel[] An array of ImportRecordModel objects representing the matching rows
     *               with the required conditions.
     */
    public function findRowsMissingDetails(int $limit = 0): array
    {
        $queryBuilder = $this->createQueryBuilderForMissingDetails();
        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }
        $queryBuilder->orderBy('priority', 'DESC');
        $queryBuilder->addOrderBy('sys_language_uid', 'ASC');
        $importResult = $queryBuilder->executeQuery();
        $importRows = [];
        $emptyRows = [];
        while ($row = $importResult->fetchAssociative()) {
            $typedIdentifier = !empty($row['source_record_uid']) ? (int)$row['source_record_uid'] : (string)$row['source_record_identifier'];
            $model = new ImportRecordModel(
                (string)$row['import_data'],
                (string)$row['source_type'],
                $typedIdentifier,
                (string)$row['data_hash'],
            );
            $model->updateFromArray($row);
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
     * Updates multiple import rows in batches based on their process type and status.
     * The method groups the imported rows by their processing states and updates them accordingly.
     *
     * @param int $importId The ID of the import process.
     * @param ImportRecordModel[] $importedRows An array of imported rows to be updated.
     */
    public function updateProcessingStatesForImportRows(int $importId, array $importedRows): void
    {
        if (empty($importedRows)) {
            return;
        }
        $finishedRows = [];
        $continueWithDataProcessingRows = [];
        $continueWithFileProcessingRows = [];
        foreach ($importedRows as $importedRow) {
            $dataProcessed = $importedRow->getDataProcessed();
            $filesProcessed = $importedRow->getFilesProcessed();
            if ($dataProcessed && $filesProcessed) {
                $finishedRows[] = $importedRow;
            } elseif ($dataProcessed) {
                $continueWithFileProcessingRows[] = $importedRow;
            } elseif ($importedRow->getDataFullyLoaded()) {
                $continueWithDataProcessingRows[] = $importedRow;
            }
        }
        $this->markRowsAsLoaded($importId, $continueWithDataProcessingRows);
        $this->markRowsAsProcessed($importId, $continueWithFileProcessingRows);
        if (!empty($finishedRows)) {
            $this->markRowsAsFinished($importId, $finishedRows, !$this->getImportOptions()->isDebug());
        }
    }

    /**
     * Registers existing source identifiers from the import table in the data transformers to prevent
     * the addition of duplicate records during processing.
     *
     * This method retrieves grouped identifiers associated with the provided import entry
     * and updates each relevant data transformer with these identifiers.
     */
    public function registerExistingIdentifiers(): void
    {
        // Register the identifiers that already exist in the import table in the data transformers to prevent
        // adding duplicate records
        $importOptions = $this->getImportOptions();
        $importId = $importOptions->getImportId();
        $groupedIdentifiers = $this->getGroupedSourceIdentifiersForImport($importId);
        foreach ($groupedIdentifiers as $targetTable => $sourceRecordIdentifiers) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            $dataTransformer->setRegisteredIdentifiers($sourceRecordIdentifiers);
        }
    }

    /**
     * Retrieves grouped source identifiers for a given import.
     * These data are used to prevent importing duplicate entries
     *
     * @param int $importId The import instance to retrieve grouped identifiers for.
     * @return array<string, array<string>> An associative array where keys are source types and values are arrays of source record IDs.
     */
    protected function getGroupedSourceIdentifiersForImport(int $importId): array
    {
        $table = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->select(ImportRecordModel::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING, 'source_type', 'sys_language_uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'import_id',
                    $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
                )
            );
        $queryBuilder->orderBy('sys_language_uid', 'ASC');
        $result = $queryBuilder->executeQuery();
        $groupedIdentifiers = [];
        while ($row = $result->fetchAssociative()) {
            $sourceRecordIdentifier = $row[ImportRecordModel::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING];
            $targetTable = $row['source_type'];
            $languageId = $row['sys_language_uid'];
            $groupedIdentifiers[$targetTable][] = $sourceRecordIdentifier . '-' . $languageId;
        }
        $result->free();
        return $groupedIdentifiers;
    }

    /**
     * Mark the import rows as loaded
     *
     * @param int|null $importId
     * @param array<ImportRecordModel> $importedRows
     */
    protected function markRowsAsLoaded(?int $importId, array $importedRows): void
    {
        if (!empty($importedRows)) {
            $queryBuilder = $this->createUpdateQueryBuilderForRecordTable($importId, $importedRows);
            $queryBuilder->set('data_fully_loaded', 1);
            $queryBuilder->executeStatement();
        }
    }

    /**
     * Mark the import rows as processed
     *
     * @param int|null $importId
     * @param array<ImportRecordModel> $importedRows
     */
    protected function markRowsAsProcessed(?int $importId, array $importedRows): void
    {
        if (!empty($importedRows)) {
            // Set the "data_processed" flag for all records
            $queryBuilder = $this->createUpdateQueryBuilderForRecordTable($importId, $importedRows);
            $queryBuilder->set('data_fully_loaded', 1);
            $queryBuilder->set('data_processed', 1);
            $queryBuilder->executeStatement();
        }
    }

    /**
     * Creates a QueryBuilder instance to fetch records with missing details from the import row table.
     *
     * This method builds a query to target rows where full data has not been loaded yet. It retrieves
     * the import options, including the import ID, and applies conditions to process only the records
     * with incomplete data.
     *
     * @return QueryBuilder The QueryBuilder instance configured to query rows with missing details.
     */
    protected function createQueryBuilderForMissingDetails(): QueryBuilder
    {
        $importOptions = $this->getImportOptions();
        $importId = $importOptions->getImportId();
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId);
        $queryBuilder->andWhere(
        // Only process records where the full data have been loaded
            $queryBuilder->expr()->eq('data_fully_loaded', $queryBuilder->createNamedParameter(
                0,
                Connection::PARAM_INT
            )),
        );
        return $queryBuilder;
    }

    /**
     * Mark all rows as finished that were already imported in the previous import entry and have not changed since
     * This prevents imported unchanged rows over and over again
     *
     * @param int $importId
     */
    protected function markRowsToBeSkipped(int $importId, array $importedTables): void
    {
        $connection = $this->getDatabaseConnection();
        $dataString = json_encode(['skipped' => 1]);
        $currentTime = time();
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            $targetTable = $dataTransformer->getTable();
            if (!in_array($targetTable, $importedTables, true)) {
                continue;
            }
            $importConfiguration = $dataTransformer->getImportConfiguration();
            $uniqueKeyField = $importConfiguration->getSourceIdentifierField();
            $importRowTable = AbstractImportRowRepository::TABLE_IMPORT_ROW;
            $sourceIdField = ImportRecordModel::getUniqueSourceIdentifierField($importConfiguration->hasIntegerIdentifiers());
            $sql = sprintf(
                'UPDATE %s a, %s p
                SET a.data_processed = 1, a.files_processed = 1, a.import_skipped = 1, a.tstamp = :currentTime, a.import_data = :dataString, a.target_record_uid = p.uid
                WHERE a.import_id = :importId AND a.source_type = :targetTable AND a.import_skipped = 0
                AND p.%s = a.%s',
                $connection->quoteIdentifier($importRowTable),
                $connection->quoteIdentifier($targetTable),
                $connection->quoteIdentifier($uniqueKeyField),
                $connection->quoteIdentifier($sourceIdField),
            );
            $languageField = $importConfiguration->getLanguageField();
            if ($languageField) {
                $sql .= sprintf(' AND p.%s = a.sys_language_uid', $languageField);
            }
            if ($dataTransformer instanceof HasLastUpdateFieldDataTransformerInterface) {
                $sql .= sprintf(
                    ' AND (a.data_hash = p.data_hash OR (a.last_updated > 0 AND a.last_updated <= p.%s))',
                    $dataTransformer->getLastUpdateTargetTcaField()
                );
            } else {
                $sql .= ' AND a.data_hash = p.data_hash';
            }

            $params = [
                'targetTable' => $targetTable,
                'importId' => $importId,
                'currentTime' => $currentTime,
                'dataString' => $dataString,
            ];

            $types = [
                'targetTable' => Connection::PARAM_STR,
                'importId' => Connection::PARAM_INT,
                'currentTime' => Connection::PARAM_INT,
                'dataString' => Connection::PARAM_STR,
            ];
            if (($targetSourceField = $importConfiguration->getTargetImportSourceField()) && $targetSourceValue = $this->getImportOptions()->getTargetImportSource()) {
                $sql .= sprintf(' AND p.%s = :targetSourceValue', $connection->quoteIdentifier($targetSourceField));
                $params['targetSourceValue'] = $targetSourceValue;
                $types['targetSourceValue'] = Connection::PARAM_STR;
            }

            try {
                $connection->executeStatement($sql, $params, $types);
            } catch (Exception $e) {
                $this->logger->error(sprintf('Marking import records as skipped import entry %d: %s', $importId, $e->getMessage()));
            }
        }
    }

    /**
     * Updates the target record IDs for all records that don't have a target record id set.
     *
     * @param int $importId The ID of the current import process used to filter records.
     * @param ImportTableConfigurationModel $importConfiguration
     * @throws Exception
     */
    public function updateTargetRecordIdsForType(int $importId, ImportTableConfigurationModel $importConfiguration): void
    {
        $importRowTable = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        $targetTable = $importConfiguration->getTableName();
        $connection = $this->getDatabaseConnection();
        $sourceIdField = ImportRecordModel::getUniqueSourceIdentifierField($importConfiguration->hasIntegerIdentifiers());
        $sql = sprintf(
            'UPDATE %s a, %s p
     SET a.target_record_uid = p.uid
     WHERE a.target_record_uid = 0
       AND a.import_id = :importId
       AND a.source_type = :targetTable
       AND p.%s = a.%s',
            $connection->quoteIdentifier($importRowTable),
            $connection->quoteIdentifier($targetTable),
            $connection->quoteIdentifier($importConfiguration->getSourceIdentifierField()),
            $connection->quoteIdentifier($sourceIdField),
        );
        $languageField = $importConfiguration->getLanguageField();
        if ($languageField) {
            $sql .= sprintf(' AND p.%s = a.sys_language_uid', $languageField);
        }
        $params = [
            'targetTable' => $targetTable,
            'importId' => $importId,
        ];

        $types = [
            'targetTable' => Connection::PARAM_STR,
            'importId' => Connection::PARAM_INT,
        ];
        $targetSourceValue = $this->getImportOptions()->getTargetImportSource();
        $targetSourceField = $importConfiguration->getTargetImportSourceField();
        if ($targetSourceField && $targetSourceValue) {
            $sql .= sprintf(' AND p.%s = :targetSourceValue', $connection->quoteIdentifier($targetSourceField));
            $params['targetSourceValue'] = $targetSourceValue;
            $types['targetSourceValue'] = Connection::PARAM_STR;
        }
        $connection->executeStatement($sql, $params, $types);
        // Set target record id's for manually translated records
        if ($languageField && $transOrigPointerField = $importConfiguration->getTransOrigPointerField()) {
            $sqlTranslations = sprintf(
                'UPDATE %s a, %s p1, %s p2
     SET a.target_record_uid = p2.uid
     WHERE a.target_record_uid = 0
       AND a.import_id = :importId
       AND a.source_type = :targetTable
       AND a.sys_language_uid > 0
       AND p1.%s = a.%s AND p1.sys_language_uid = 0 AND p2.sys_language_uid = a.sys_language_uid AND p2.%s = p1.uid',
                $connection->quoteIdentifier($importRowTable),
                $connection->quoteIdentifier($targetTable),
                $connection->quoteIdentifier($targetTable),
                $connection->quoteIdentifier($importConfiguration->getSourceIdentifierField()),
                $connection->quoteIdentifier($sourceIdField),
                $connection->quoteIdentifier($transOrigPointerField),
            );
            if ($targetSourceField && $targetSourceValue) {
                $sqlTranslations .= sprintf(' AND p1.%s = :targetSourceValue', $connection->quoteIdentifier($targetSourceField));
                $sqlTranslations .= sprintf(' AND p2.%s = :targetSourceValueP2', $connection->quoteIdentifier($targetSourceField));
                $params['targetSourceValueP2'] = $targetSourceValue;
                $types['targetSourceValueP2'] = Connection::PARAM_STR;
            }
            $connection->executeStatement($sqlTranslations, $params, $types);
        }
    }
}
