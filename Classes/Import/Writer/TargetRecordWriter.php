<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Event\RecordInsertedEvent;
use BrainAppeal\CampusEventsConnector\Import\Event\RecordUpdatedEvent;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ReferenceResolver;
use Doctrine\DBAL\ArrayParameterType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the persistence of the target records into the database.
 */
class TargetRecordWriter
{
    /**
     * @var array<string, string[]>
     */
    protected array $errorsByTable = [];

    public function __construct(protected readonly EventDispatcherInterface $eventDispatcher, protected readonly LoggerInterface $logger) {}

    /**
     * Retrieves the errors grouped by the corresponding database tables.
     *
     * @return array<string, string[]> An associative array where the keys represent table names
     * and the values are arrays containing error details for each table. Returns an empty array if no errors are recorded.
     */
    public function getErrorsByTable(): array
    {
        return $this->errorsByTable;
    }

    /**
     * Create a new target record for the import model
     *
     * @param ImportTableConfigurationModel $importConfiguration
     * @param ImportRecordModel $model
     * @param ReferenceResolver $referenceResolver
     * @return void
     */
    public function createRecord(ImportTableConfigurationModel $importConfiguration, ImportRecordModel $model, ReferenceResolver $referenceResolver): void
    {
        $targetTable = $importConfiguration->getTableName();
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($targetTable);
        $data = $model->getTransformedData();
        try {
            $queryBuilder->insert($targetTable)
                ->values($data)
                ->executeStatement();
            $uid = (int)$queryBuilder->getConnection()->lastInsertId();
            if ($uid > 0) {
                $data['uid'] = $uid;
                $model->setPersistedData($data);
                $model->setTargetRecordId($uid);
                $referenceResolver->addMappingForModel($importConfiguration, $model);
                $event = new RecordInsertedEvent($uid, $targetTable, $model);
                $this->eventDispatcher->dispatch($event);
            }
        } catch (\Throwable $e) {
            $this->errorsByTable[$targetTable][] = $e->getMessage();
            $this->logger->error($e->getMessage(), ['table' => $targetTable, 'row' => $data]);
        }
    }

    /**
     * Update the target records for the import models
     *
     * @param ImportTableConfigurationModel $importConfiguration The import table configuration model containing table information.
     * @param ImportRecordModel[] $importModels An array of import model objects to be processed for updates.
     * @param ReferenceResolver $referenceResolver A mapping object used to track processed records and their statuses.
     * @return int The total number of rows successfully updated.
     */
    public function updateRecords(ImportTableConfigurationModel $importConfiguration, array $importModels, ReferenceResolver $referenceResolver): int
    {
        $mapping = $referenceResolver->getMapping();
        $targetTable = $importConfiguration->getTableName();
        $updatedRowCount = 0;
        $events = [];
        $this->hydrateModelsWithCurrentData($targetTable, $importModels);
        $connection = $this->getDatabaseConnection($targetTable);
        foreach ($importModels as $model) {
            $uid = $model->getTargetRecordId();
            $changedData = $this->computeChangedData($importConfiguration, $model);
            if (!empty($mmReferences = $model->getManyToManyReferences())) {
                $mapping->addManyToManyReference($targetTable, $uid, $mmReferences);
            }
            if (empty($changedData)) {
                continue;
            }
            ++$updatedRowCount;
            try {
                $connection->update($targetTable, $changedData, ['uid' => $uid]);
            } catch (\Throwable $e) {
                $this->errorsByTable[$targetTable][] = $e->getMessage();
                $this->logger->error($e->getMessage(), ['table' => $targetTable, 'uid' => $uid, 'row' => $changedData]);
            }
            $mapping->addProcessed($targetTable, $uid, true, false);
            $events[] = new RecordUpdatedEvent($uid, $targetTable, $model);
        }
        foreach ($events as $event) {
            $this->eventDispatcher->dispatch($event);
        }
        return $updatedRowCount;
    }

    /**
     * Updates model data with the current state from the database for a specific table.
     *
     * This method retrieves existing records from the database that correspond to the provided models,
     * and synchronizes these models with the database rows. It ensures that deleted or hidden records
     * are reactivated if necessary and updates the models with the fetched persisted data.
     *
     * @param string $table The name of the database table to query.
     * @param ImportRecordModel[] $modelsForUpdate Array of models that need to be hydrated with current database data.
     */
    protected function hydrateModelsWithCurrentData(string $table, array $modelsForUpdate): void
    {
        $mappedModels = [];
        $uidList = [];
        $modelsByUid = [];
        foreach ($modelsForUpdate as $model) {
            $targetRecordId = $model->getTargetRecordId();
            if ($targetRecordId) {
                $modelsByUid[$targetRecordId] = $model;
                $uidList[] = $targetRecordId;
            }
        }
        if (empty($uidList)) {
            return;
        }

        // Fetch existing rows from DB for comparison
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($table);
        $existingRows = $connection->fetchAllAssociative(
            'SELECT * FROM ' . $table . ' WHERE uid IN (:uidList)',
            ['uidList' => $uidList],
            ['uidList' => ArrayParameterType::INTEGER]
        );

        // Index existing rows by uid for O(1) lookup
        foreach ($existingRows as $row) {
            $uid = (int)$row['uid'];
            if ($targetModel = $modelsByUid[$uid] ?? null) {
                $targetModel->setPersistedData($row);
                $mappedModels[] = $targetModel;
            }
        }
        $this->updateMappedModelDeletedAndHiddenFlags($table, $mappedModels);
    }

    /**
     * Updates the `deleted` and `hidden` flags for mapped models in the specified database table.
     *
     * @param string $table The name of the database table where the flags will be updated.
     * @param ImportRecordModel[] $mappedModels An array of mapped model objects containing persisted data, used to determine which records need to have their flags updated.
     */
    protected function updateMappedModelDeletedAndHiddenFlags(string $table, array $mappedModels): void
    {
        $connection = $this->getDatabaseConnection($table);

        $undeleteUids = [];
        $enableUids = [];
        // Index existing rows by uid for O(1) lookup
        foreach ($mappedModels as $model) {
            $row = $model->getPersistedData();
            $uid = (int)$row['uid'];
            if (array_key_exists('deleted', $row) && (int)$row['deleted'] === 1) {
                $undeleteUids[] = $uid;
                $row['deleted'] = 0;
            }
            if (array_key_exists('hidden', $row) && (int)$row['hidden'] === 1) {
                $enableUids[] = $uid;
                $row['hidden'] = 0;
            }
        }
        if (!empty($undeleteUids)) {
            $connection->executeStatement('UPDATE ' . $table . ' SET deleted = 0 WHERE uid IN (' . implode(',', $undeleteUids) . ')');
        }
        if (!empty($enableUids)) {
            $connection->executeStatement('UPDATE ' . $table . ' SET hidden = 0 WHERE uid IN (' . implode(',', $enableUids) . ')');
        }
    }

    /**
     * Filters and determines the changes in the data for a given import record model,
     * comparing it to the original data in the database.
     *
     * @param ImportTableConfigurationModel $importConfiguration The import table configuration model containing table information.
     * @param ImportRecordModel $hydratedModel The import record model containing transformed and original data for comparison.
     * @return array<string, mixed> An associative array of fields and their updated values that have changed. Returns an empty array if no changes are detected.
     */
    private function computeChangedData(ImportTableConfigurationModel $importConfiguration, ImportRecordModel $hydratedModel): array
    {
        $uid = $hydratedModel->getTargetRecordId();
        if ($uid <= 0) {
            return [];
        }
        $table = $importConfiguration->getTableName();

        $connection = $this->getDatabaseConnection($table);
        $compareIgnoreFields = $importConfiguration->getCompareIgnoreFields();
        // Always-include these fields once a change is detected
        $includeOnChange = ['uid', 'tstamp'];
        $transformedData = $hydratedModel->getTransformedData();
        $filtered = array_diff_key($transformedData, array_flip($compareIgnoreFields));
        $persistedData = $hydratedModel->getPersistedData();
        $changed = [];
        $changedValuesBeforeUpdate = [];
        foreach ($filtered as $field => $value) {
            $existingValue = $persistedData[$field] ?? null;
            // Normalize to string for comparison to avoid int/string mismatch issues from DBAL
            if ((string)$existingValue !== (string)$value) {
                $changed[$field] = $value;
                $changedValuesBeforeUpdate[$field] = $existingValue;
            }
        }
        if (!empty($changed)) {
            // The data_hash is only used for the import and does not need further processing.
            // Therefore, we perform a direct update here instead of using the DataHandler
            if (isset($changed['data_hash']) && count($changed) === 1) {
                $connection->update($table, ['data_hash' => $changed['data_hash']], ['uid' => $uid]);
            } else {
                $hydratedModel->setChangedValuesBeforeUpdate($changedValuesBeforeUpdate);

                // Always include these meta-fields if present in the import row
                foreach ($includeOnChange as $alwaysField) {
                    if ($alwaysField === 'uid') {
                        $changed['uid'] = $uid;
                        continue;
                    }
                    if (array_key_exists($alwaysField, $filtered)) {
                        $changed[$alwaysField] = $filtered[$alwaysField];
                    }
                }
            }
        }
        // If no changes detected, skip this row entirely
        return $changed;
    }

    /**
     * Retrieves a database connection for the specified table.
     *
     * This method uses the ConnectionPool to fetch a database connection
     * that matches the given table's configuration.
     *
     * @param string $table The name of the database table for which the connection is required.
     * @return Connection The database connection associated with the specified table.
     */
    protected function getDatabaseConnection(string $table): Connection
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($table);
    }
}
