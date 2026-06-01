<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataCollection;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\AbstractDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportRecordWriter;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportContext;

/**
 * Abstract class that defines a data collection interface for handling
 * and importing data records, managing dependencies, and providing
 * utilities for processing and prioritizing data transformers.
 */
abstract class AbstractDataCollection
{
    protected int $processingStart;
    protected int $totalRowsToImport = 0;

    /**
     * Enable debug mode
     */
    protected bool $debug = false;

    public function __construct(
        protected readonly ImportRecordWriter $importRecordWriter,
        protected readonly DataTransformerFactory $dataTransformerFactory
    ) {
        $this->processingStart = time();
    }

    abstract public function canProvide(ImportContext $context): bool;

    /**
     * Returns either a unix timestamp with the last modification date or null if it is unknown
     * @return int|null
     */
    abstract public function getDataSourceLastModified(): ?int;

    /**
     * Collects import data based on the specified import conditions.
     *
     * @param ImportContext $context
     * @return int The number of collected rows
     */
    abstract public function collectRawData(ImportContext $context): int;

    /**
     * Attempts to fetch the remaining data for the provided import record model, in case the data are loaded in multiple steps
     *
     * @param ImportContext $context
     * @param ImportRecordModel $model The import record model for which to fetch remaining data
     * @param ImportRecordModel[] $newRows An array passed by reference to collect new rows for processing.
     * @return bool True if the remaining data could be successfully fetched, false otherwise
     */
    abstract protected function tryFetchRemainingDataForModel(ImportContext $context, ImportRecordModel $model, array &$newRows): bool;

    /**
     * Retrieves the number of API calls made.
     *
     * @return int The total count of API calls made.
     */
    abstract public function getNumberOfApiCallsMade(): int;

    /**
     * Retrieves the total number of API calls that have been cached locally for development purposes.
     *
     * @return int The number of cached API calls made
     */
    public function getNumberOfCachedApiCallsMade(): int
    {
        return 0;
    }

    public function getTotalRowsToImport(): int
    {
        return $this->totalRowsToImport;
    }

    /**
     * Determines whether the data collection process is complete.
     *
     * This method checks if all data rows have been fully detailed and no rows
     * are missing details in the data collection process.
     *
     * @param ImportContext $context
     * @return bool Returns true if all rows have complete details; otherwise, false.
     */
    public function isComplete(ImportContext $context): bool
    {
        return $this->importRecordWriter->countRowsMissingDetails($context) === 0;
    }

    /**
     * Adds recursive references for a given import row to the provided rows array.
     *
     * This method processes child records associated with the provided import row,
     * based on source types and their respective data transformers. It determines
     * whether child records are lists or single entries, recursively adds them, and
     * optionally saves the rows if their quantity exceeds the defined threshold.
     *
     * @param ImportContext $context
     * @param ImportRecordModel $importRecordModel The import record model containing the data to process.
     * @param ImportRecordModel[] &$rows The array of rows where new records and references will be added.
     */
    protected function postProcessAfterModelAdded(ImportContext $context, ImportRecordModel $importRecordModel, array &$rows): void
    {
        $targetTable = $importRecordModel->getTargetTable();
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
        // Make files as processed if either the data type has no files or the current record has no files
        if ($dataTransformer->hasFileTransformations()) {
            $noFileProcessingRequired = !$dataTransformer->getFileDataTransformerHelper()->requiresFileProcessing($importRecordModel->getImportData());
            $importRecordModel->setFilesProcessed($noFileProcessingRequired);
        } else {
            $importRecordModel->setFilesProcessed(true);
        }
        if ($dataTransformer instanceof AbstractDataTransformer && $referenceTypes = $dataTransformer->getChildRecordTypesForSourceType()) {
            foreach ($referenceTypes as $referenceTable => $typeConfig) {
                $this->addReferencesForType($context, $importRecordModel, $referenceTable, $typeConfig, $rows);
            }
        }
        $dataTransformer->postProcessAfterModelAdded($importRecordModel);
        // Save rows if the array contains too many entries
        if (count($rows) >= 500) {
            $this->importRecordWriter->addRows($context, $rows);
            $rows = [];
        }
    }

    /**
     * Adds references for a specific type to the provided import records.
     *
     * @param ImportContext $context
     * @param ImportRecordModel $importRecordModel The import record model containing the main data.
     * @param string $referenceTable The target table of the references to be added.
     * @param array{identifier: string|string[], isListType: bool, isManyToMany: ?bool, referenceFieldName: ?string} $typeConfig Reference field configuration.
     * @param ImportRecordModel[] $rows The array where the updated or newly created reference records will be added.
     * @throws \JsonException
     */
    private function addReferencesForType(ImportContext $context, ImportRecordModel $importRecordModel, string $referenceTable, array $typeConfig, array &$rows): void
    {
        $fieldIdentifier = $typeConfig['identifier'];
        $isListType = $typeConfig['isListType'];
        $parentTargetTable = $importRecordModel->getTargetTable();
        $refDataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $referenceTable);
        // If we update from the full dump, all organization API data are already loaded from the API
        // Otherwise the data are not fully loaded, if the source type has an API endpoint
        // While the person data SEEM to be also included in the full dump, this is not the case:
        // Most of the extra fields like images or vita are always empty in the organization data
        $dataFullyLoaded = !$refDataTransformer::getApiField();
        $importData = $importRecordModel->getImportData();
        if ($isListType) {
            $isManyToMany = $typeConfig['isManyToMany'] ?? false;
            $referenceFieldName = $typeConfig['referenceFieldName'] ?? null;
            // Get a record list and unset the field in the parent import data (to save space)
            $recordList = $refDataTransformer->extractRecordListFromImportData($importData, $fieldIdentifier, true);
            if ($isManyToMany) {
                $refIdList = [];
                foreach ($recordList as $record) {
                    $refModel = $this->addImportRecordModel($context, $record, $refDataTransformer, $dataFullyLoaded, $rows);
                    if ($refModel) {
                        $importIdentifier = $refModel->getSourceRecordIdentifier();
                    } else {
                        $importIdentifier = $refDataTransformer->getRawDataConverter()->getImportIdentifier($record);
                    }
                    if ($importIdentifier) {
                        $refIdList[] = $importIdentifier;
                    }
                }
                if ($referenceFieldName) {
                    $importData[$referenceFieldName] = $refIdList;
                }
                $mmDefaultRefFieldName = $refDataTransformer::INTERNAL_KEY_PREFIX . 'mm_' . $fieldIdentifier . '_id';
                // Fallback for backward compatibility
                $importData[$mmDefaultRefFieldName] = $refIdList;
            } else {
                if (!$referenceFieldName) {
                    $referenceFieldName = $refDataTransformer::INTERNAL_KEY_PREFIX . 'parent_' . $parentTargetTable . '_id';
                }
                foreach ($recordList as $record) {
                    $record[$referenceFieldName] = $importRecordModel->getSourceRecordIdentifier();
                    $this->addImportRecordModel($context, $record, $refDataTransformer, $dataFullyLoaded, $rows);
                }
            }
            // Get record and unset the field in the parent import data (to save space)
        } elseif ($record = $refDataTransformer->extractRecordFromImportData($importData, $fieldIdentifier, true)) {
            $this->addImportRecordModel($context, $record, $refDataTransformer, $dataFullyLoaded, $rows);
        }
        // Update the raw import data without the child reference data to reduce the required space
        $importRecordModel->updateImportData($importData, false);
    }

    /**
     * Creates and returns an ImportRecordModel instance with the provided data.
     *
     * @param ImportContext $context
     * @param array<string, mixed> $record The record data to be used in creating the import model.
     * @param ImportDataTransformerInterface $refDataTransformer Transformer handling specific import data operations.
     * @param bool $dataFullyLoaded Indicates whether the data is already fully loaded.
     * @param ImportRecordModel[] $rows
     */
    protected function addImportRecordModel(
        ImportContext $context,
        array $record,
        ImportDataTransformerInterface $refDataTransformer,
        bool $dataFullyLoaded,
        array &$rows
    ): ?ImportRecordModel {
        $model = $refDataTransformer->initializeImportRecord($record);
        if ($model === null) {
            return null;
        }
        $model->setPriority($refDataTransformer->getPriority($model->getImportData()));
        $model->setCrdate($this->processingStart);
        $model->setDataFullyLoaded($dataFullyLoaded);
        $rows[] = $model;
        ++$this->totalRowsToImport;
        if ($model->getDataFullyLoaded()) {
            $this->postProcessAfterModelAdded($context, $model, $rows);
        } else {
            $this->tryFetchRemainingDataForModel($context, $model, $rows);
        }
        return $model;
    }
}
