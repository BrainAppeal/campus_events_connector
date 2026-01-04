<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\DataCollection;

use BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\DefaultDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiLimitReachedException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiRecordNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\DataCollection\AbstractDataCollection;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;

class CeApiDataCollection extends AbstractDataCollection
{
    /**
     * If set to false, no further API calls will be made during this run, but the remaining records will be processed
     */
    protected bool $apiAccessEnabled = true;
    private CeApiConnector $apiConnector;

    /**
     * @var array<string, int>
     */
    private array $languageMap = ['de' => 0];

    public function setApiConnector(CeApiConnector $apiConnector): void
    {
        $this->apiConnector = $apiConnector;
    }

    public function canProvide(): bool
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

    public function collectRawData(bool $isNewImport): int
    {
        if ($isNewImport) {
            $this->startProcessingForNewImport();
            return $this->totalRowsToImport;
        }
        return 0;
    }

    /**
     * Sets the language map used for mapping language codes to their corresponding identifiers.
     *
     * @param array $languageMap An associative array where the key is the language code and the value is the language identifier.
     * @return void
     */
    public function setLanguageMap(array $languageMap): void
    {
        $this->languageMap = $languageMap;
    }

    /**
     * Starts a new import process. This method is intended to set up any necessary
     * resources, configurations, or states required to start a new import operation.
     */
    protected function startProcessingForNewImport(): void
    {
        gc_enable();
        $rows = [];
        $apiConnector = $this->apiConnector;
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            /** @var DefaultDataTransformer $dataTransformer */
            $dataFullyLoaded = $dataTransformer->isApiListItemContainsAllData();
            foreach ($this->languageMap as $languageCode => $languageUid) {
                $listItems = $apiConnector->fetchItemListForType($dataTransformer, $languageCode);
                foreach ($listItems as $record) {
                    $record['_language_id'] = $languageUid;
                    $record['_language_code'] = $languageCode;
                    $this->addImportRecordModel($record, $dataTransformer, $dataFullyLoaded, $rows);
                }
            }
        }
        gc_collect_cycles();
        if (!empty($rows)) {
            $this->importRecordWriter->addRows($rows);
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
    protected function tryFetchRemainingDataForModel(ImportRecordModel $model, array &$newRows): bool
    {
        // Import detailed data until limit is reached
        if ($this->apiAccessEnabled && !$model->getDataFullyLoaded()) {
            $refDataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($model->getTargetTable());
            $record = $model->getImportData();
            try {
                $languageCode = $record['_language_code'] ?? null;
                $fullRecordData = $this->apiConnector->getApiResponse($record[CeApiConnector::ID_FIELD], [], $languageCode);
                if ($fullRecordData) {
                    $fullRecordData = array_merge($record, $fullRecordData);
                    $refDataTransformer->updateImportRecord($model, $fullRecordData);
                    $model->setDataFullyLoaded(true);
                    $this->postProcessAfterModelAdded($model, $newRows);
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
