<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use Doctrine\DBAL\ArrayParameterType;
use BrainAppeal\CampusEventsConnector\Import\Event\RecordInsertedEvent;
use BrainAppeal\CampusEventsConnector\Import\Event\RecordUpdatedEvent;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ReferenceResolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the persistence of the target records into the database.
 */
readonly class TargetRecordWriter
{
    public function __construct(protected EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * Insert or update the target records with the transformed import data
     *
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel $importConfiguration
     * @param ImportRecordModel[] $importModels
     * @param ReferenceResolver $referenceResolver
     * @param bool $doCreateRecords Indicates if new or updated rows are written
     * @return int The number of newly inserted rows.
     */
    public function writeRecords(ImportTableConfigurationModel $importConfiguration, array $importModels, ReferenceResolver $referenceResolver, bool $doCreateRecords): int
    {
        if (empty($importModels)) {
            return 0;
        }
        if ($doCreateRecords) {
            return $this->createRecords($importConfiguration, $importModels, $referenceResolver);
        }
        return $this->updateRecords($importConfiguration, $importModels, $referenceResolver);
    }

    /**
     * Create new target records for the import models
     *
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel $importConfiguration
     * @param ImportRecordModel[] $importModels
     * @param ReferenceResolver $referenceResolver
     * @return int The number of newly inserted rows.
     */
    protected function createRecords(ImportTableConfigurationModel $importConfiguration, array $importModels, ReferenceResolver $referenceResolver): int
    {
        $bulkReplaceService = GeneralUtility::makeInstance(AdvancedBulkReplaceService::class);
        $uniqueTargetIdentifierField = $importConfiguration->getSourceIdentifierField();
        $targetTable = $importConfiguration->getTableName();
        $modelsByKeyAndLanguage = [];
        $importKeyByCompositeKey = [];
        $rowsForInsert = [];
        foreach ($importModels as $model) {
            $data = $model->getTransformedData();
            $rowsForInsert[] = $data;
            if ($targetIdentifier = (string)($data[$uniqueTargetIdentifierField] ?? null)) {
                $keyWithLanguage = sprintf('%s:%d', $targetIdentifier, $model->getLanguageUid());
                $modelsByKeyAndLanguage[$keyWithLanguage] = $model;
                $importKeyByCompositeKey[$keyWithLanguage] = $targetIdentifier;
            }
        }
        $result = $bulkReplaceService->bulkReplace($targetTable, $rowsForInsert);
        if (!empty($result['errors'])) {
            throw new \RuntimeException(sprintf('Bulk replace failed: %s; Source type %s', print_r($result['errors'], true), $targetTable));
        }
        $mappedModels = $referenceResolver->mapInsertedModels($importConfiguration, $modelsByKeyAndLanguage, $importKeyByCompositeKey);
        $insertedRowCount = count($rowsForInsert);
        foreach ($mappedModels as $mappedModel) {
            $id = $mappedModel->getTargetRecordId();
            $event = new RecordInsertedEvent($id, $targetTable, $mappedModel);
            $this->eventDispatcher->dispatch($event);
        }
        return $insertedRowCount;
    }

    /**
     * Update the target records for the import models
     *
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel $importConfiguration The import table configuration model containing table information.
     * @param ImportRecordModel[] $importModels An array of import model objects to be processed for updates.
     * @param ReferenceResolver $referenceResolver A mapping object used to track processed records and their statuses.
     * @return int The total number of rows successfully updated.
     */
    protected function updateRecords(ImportTableConfigurationModel $importConfiguration, array $importModels, ReferenceResolver $referenceResolver): int
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
            $connection->update($targetTable, $changedData, ['uid' => $uid]);
            $mapping->addProcessed($targetTable, $uid, $model->isUnchanged(), false);
            if (!$model->isUnchanged()) {
                $events[] = new RecordUpdatedEvent($uid, $targetTable, $model);
            }
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
     *
     * @return void
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
     * @return void
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
        foreach ($filtered as $field => $value) {
            $existingValue = $persistedData[$field] ?? null;
            // Normalize to string for comparison to avoid int/string mismatch issues from DBAL
            if ((string)$existingValue !== (string)$value) {
                $changed[$field] = $value;
            }
        }
        if (!empty($changed)) {
            // The data_hash is only used for the import and does not need further processing.
            // Therefore, we perform a direct update here instead of using the DataHandler
            if (isset($changed['data_hash']) && count($changed) === 1) {
                $connection->update($table, ['data_hash' => $changed['data_hash']], ['uid' => $uid]);
            } else {
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
