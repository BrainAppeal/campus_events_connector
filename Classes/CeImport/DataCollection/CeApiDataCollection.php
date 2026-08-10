<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\DataCollection;

use BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\DefaultDataTransformer;
use BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\EventDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\DataCollection\AbstractDataCollection;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiLimitReachedException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiRecordNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Exception\StopImportException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportContext;
use BrainAppeal\CampusEventsConnector\Utility\TCAUtility;

class CeApiDataCollection extends AbstractDataCollection
{
    /**
     * If set to false, no further API calls will be made during this run, but the remaining records will be processed
     */
    protected bool $apiAccessEnabled = true;

    protected bool $delayLoadingDetails = false;
    private CeApiConnector $apiConnector;

    /**
     * @var array<string, int>
     */
    private array $languageMap = ['de' => 0];

    public function setApiConnector(CeApiConnector $apiConnector): void
    {
        $this->apiConnector = $apiConnector;
    }

    public function canProvide(ImportContext $context): bool
    {
        return $this->apiConnector->isConfigured();
    }

    /**
     * Returns either a unix timestamp with the last modification date or null if it is unknown
     * @return int|null
     */
    public function getDataSourceLastModified(): ?int
    {
        return null;
    }

    /**
     * Retrieves the number of API calls made.
     *
     * @return int The total count of API calls made.
     */
    public function getNumberOfApiCallsMade(): int
    {
        return $this->apiConnector->getCountApiCalls();
    }

    /**
     * Retrieves the total number of API calls that have been cached.
     *
     * @return int The number of cached API calls made
     */
    public function getNumberOfCachedApiCallsMade(): int
    {
        return $this->apiConnector->getCountCachedApiCalls();
    }

    public function collectRawData(ImportContext $context): int
    {
        if ($context->isStartOfNewImport()) {
            $this->startProcessingForNewImport($context);
            return $this->totalRowsToImport;
        }
        return 0;
    }

    /**
     * Sets the language map used for mapping language codes to their corresponding identifiers.
     *
     * @param array $languageMap An associative array where the key is the language code and the value is the language identifier.
     */
    public function setLanguageMap(array $languageMap): void
    {
        $this->languageMap = $languageMap;
    }

    /**
     * Starts a new import process. This method is intended to set up any necessary
     * resources, configurations, or states required to start a new import operation.
     */
    protected function startProcessingForNewImport(ImportContext $context): void
    {
        gc_enable();
        $importOptions = $context->options;
        $this->delayLoadingDetails = !$importOptions->isForceUpdate();
        $eventSourceIdList = $this->fetchAndAddEventItems($context);
        /** @var ImportRecordModel[] $rows */
        $rows = [];
        if ($importOptions->isForceUpdate()) {
            foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
                if ($dataTransformer->getTable() === TCAUtility::TABLE_EVENTS) {
                    continue;
                }
                /** @var DefaultDataTransformer $dataTransformer */
                $dataTransformer->setValidEventSourceIds($eventSourceIdList);
                $this->fetchAndAddRowsForDataTransformer($context, $dataTransformer, $rows);
            }
            gc_collect_cycles();
            if (!empty($rows)) {
                $this->importRecordWriter->addRows($context, $rows);
            }
            return;
        }
        $importId = $context->getImportId();
        $incompleteRows = $this->importRecordWriter->findIncompleteImportRows($importId, TCAUtility::TABLE_EVENTS);
        if ($incompleteRows === []) {
            $message = sprintf('All event rows are marked as skipped for import id %d. Stopping import process.', $importId);
            throw new StopImportException($message, StopImportException::CODE_STOP_IMPORT_NO_DATA);
        }
        foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
            if ($dataTransformer->getTable() === TCAUtility::TABLE_EVENTS) {
                continue;
            }
            /** @var DefaultDataTransformer $dataTransformer */
            $dataTransformer->setValidEventSourceIds($eventSourceIdList);
            $this->fetchAndAddRowsForDataTransformer($context, $dataTransformer, $rows);
        }
        gc_collect_cycles();
        if (!empty($rows)) {
            $this->importRecordWriter->addRows($context, $rows);
        }
        // Only load the record details for rows that are not skipped
        $incompleteRows = $this->importRecordWriter->findIncompleteImportRows($importId);
        if ($incompleteRows !== []) {
            $dummyRows = [];
            foreach ($incompleteRows as $model) {
                $this->fetchRemainingDataForModel($context, $model, $dummyRows);
            }
            $this->importRecordWriter->update($incompleteRows);
        }
    }

    /**
     * Fetches and processes event items by utilizing a data transformer and appends them to the import record writer.
     *
     * This method retrieves event-related rows, transforms them using a specific data transformer, and prepares
     * the source record IDs of these events for further use.
     *
     * @return array List of source record UIDs associated with the processed event items.
     */
    protected function fetchAndAddEventItems(ImportContext $context): array
    {
        /** @var ImportRecordModel[] $rows */
        $rows = [];
        // The source ids must be collected while the rows are created, because the rows array is written to the
        // database and reset as soon as it contains too many entries (@see postProcessAfterModelAdded)
        $eventSourceIdList = [];
        // Save the event rows first. If no event was changed, we can stop the import process here.
        $eventDataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, TCAUtility::TABLE_EVENTS);
        /** @var EventDataTransformer $eventDataTransformer */
        $this->fetchAndAddRowsForDataTransformer($context, $eventDataTransformer, $rows, $eventSourceIdList);
        $this->importRecordWriter->addRows($context, $rows);
        return $eventSourceIdList;
    }

    /**
     * Adds rows for the specified data transformer by processing API list items for each language
     * in the language map. Updates the rows array with import record models derived from the API data.
     *
     * @param ImportContext $context
     * @param DefaultDataTransformer $dataTransformer The data transformer used to process and transform API records.
     * @param array &$rows The array to be updated with import record models created from the API data.
     * @param array|null &$sourceRecordUidList Optional array that is filled with the source record uids of all added records.
     */
    protected function fetchAndAddRowsForDataTransformer(ImportContext $context, DefaultDataTransformer $dataTransformer, array &$rows, ?array &$sourceRecordUidList = null): void
    {
        $dataFullyLoaded = $dataTransformer->isApiListItemContainsAllData();
        foreach ($this->languageMap as $languageCode => $languageUid) {
            $listItems = $this->apiConnector->fetchItemListForType($dataTransformer, $languageCode);
            foreach ($listItems as $record) {
                $record['_language_id'] = $languageUid;
                $record['_language_code'] = $languageCode;
                $model = $this->addImportRecordModel($context, $record, $dataTransformer, $dataFullyLoaded, $rows);
                if ($model !== null && $sourceRecordUidList !== null) {
                    // Use the uid as array key to prevent duplicate entries for translated records
                    $sourceRecordUid = $model->getSourceRecordUid();
                    $sourceRecordUidList[$sourceRecordUid] = $sourceRecordUid;
                }
            }
        }
    }

    /**
     * Loads full API data for the given import record model if required and supported.
     * Ensures detailed data is loaded for the model based on its source type and API constraints.
     *
     * @param ImportRecordModel $model The import record model for which detailed API data may be loaded.
     * @param ImportRecordModel[] $newRows An array passed by reference to collect new rows for processing.
     * @return bool True, if the full API data was successfully loaded and updated in the model, false otherwise.
     */
    protected function tryFetchRemainingDataForModel(ImportContext $context, ImportRecordModel $model, array &$newRows): bool
    {
        if ($this->delayLoadingDetails) {
            return false;
        }
        return $this->fetchRemainingDataForModel($context, $model, $newRows);
    }

    /**
     * Loads full API data for the given import record model if required and supported.
     * Ensures detailed data is loaded for the model based on its source type and API constraints.
     *
     * @param ImportContext $context
     * @param ImportRecordModel $model The import record model for which detailed API data may be loaded.
     * @param ImportRecordModel[] $rows An array passed by reference to collect new rows for processing.
     * @return bool True, if the full API data was successfully loaded and updated in the model, false otherwise.
     */
    private function fetchRemainingDataForModel(ImportContext $context, ImportRecordModel $model, array &$rows): bool
    {
        // Import detailed data until limit is reached
        if ($this->apiAccessEnabled && !$model->getDataFullyLoaded()) {
            $refDataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $model->getTargetTable());
            $record = $model->getImportData();
            try {
                $languageCode = $record['_language_code'] ?? null;
                $fullRecordData = $this->apiConnector->getApiResponse($record[CeApiConnector::ID_FIELD], [], $languageCode);
                if ($fullRecordData !== []) {
                    $fullRecordData = array_merge($record, $fullRecordData);
                    $refDataTransformer->updateImportRecord($model, $fullRecordData);
                    $model->setDataFullyLoaded(true);
                    $this->postProcessAfterModelAdded($context, $model, $rows);
                    return true;
                }
                // The record was apparently deleted in the API data
            } catch (ApiRecordNotFoundException) {
                $model->setDataFullyLoaded(true);
                $model->setDataProcessed(true);
                $model->setFilesProcessed(true);
                $model->setIsInvalidated(true);
                return true;
            } catch (ApiLimitReachedException) {
                $this->apiAccessEnabled = false;
            }
        }
        return false;
    }
}
