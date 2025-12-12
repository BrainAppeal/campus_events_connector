<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Http\HttpException;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator\ExtendedSpecifiedImportObjectGenerator;
use BrainAppeal\CampusEventsConnector\Utility\ImportScheduleUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class ExtendedImporter
{
    /**
     * @var ExtendedApiConnector $apiConnector
     */
    protected $apiConnector;

    protected ?ImportScheduleUtility $importScheduleUtility = null;

    /**
     * Toggle debug mode
     * @var bool|string
     */
    private $debug = false;

    /**
     * Indicates if any data were changed
     *
     * @var bool
     */
    private bool $hasChangedData = false;

    /**
     * @var array
     */
    protected array $exceptions = [];

    public function __construct(ExtendedApiConnector $apiConnector, private readonly TranslationHandler $translationHandler)
    {
        $this->apiConnector = $apiConnector;
    }

    /**
     * Run the import task
     *
     * @param string $baseUri The API base uri
     * @param string $apiKey The API key
     * @param int $pid The storage page id
     * @param int $storageId The storage id
     * @param string $storageFolder The storage folder
     * @return bool Returns true if execution was successful, false otherwise
     * @throws HttpException
     */
    public function import(string $baseUri, string $apiKey, int $pid, int $storageId, string $storageFolder): bool
    {
        $this->initializeExtbaseEnvironment($pid);
        // Enable debug mode to keep queue item data (prevent repeated API access for the same data)
        // + force update of all found items
        //$this->debug = true;//'forceUpdate'
        if (!$this->debug || $this->debug === 'forceUpdate') {
            $this->getImportScheduleUtility()->cleanUp();
        }
        $importStartTimestamp = time();
        $apiConnector = $this->apiConnector;
        $apiConnector->setBaseUri($baseUri);
        $apiConnector->setApiKey($apiKey);
        if ($this->debug) {
            $apiConnector->setDevelopmentLocalCacheAgeInDays(7);
        }
        $languageOverlays = $this->translationHandler->getPageLanguageOverlays($pid);
        if (!empty($languageOverlays)) {
            $extraLanguageCodes = array_column($languageOverlays, 'language_code');
            $apiConnector->setAcceptLanguages($extraLanguageCodes);
        }
        // If we have a lot of data some day, the number of processed items can be limited, and instead of fetching the
        // api data, we can process the stored data in the unprocessed queue items
        $this->fetchDataFromApi($apiConnector, $importStartTimestamp);
        /** @var ImportScheduleUtility $importScheduleUtility */
        $importScheduleUtility = GeneralUtility::makeInstance(ImportScheduleUtility::class);
        if ($this->hasChangedData || (int)$importScheduleUtility->countUnprocessedScheduleEntries() > 0) {
            $dataMap = $apiConnector->getDataMap();
            /** @var ExtendedFileImporter $fileImporter */
            $fileImporter = GeneralUtility::makeInstance(ExtendedFileImporter::class);
            $fileImporter->initialize($storageId, $storageFolder, $baseUri);
            $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($baseUri);
            try {
                /** @var ExtendedSpecifiedImportObjectGenerator $importObjectGenerator */
                $importObjectGenerator = GeneralUtility::makeInstance(ExtendedSpecifiedImportObjectGenerator::class);
                $groupedImportMappingModels = $importObjectGenerator->processQueue($dataMap, $dbImportSource, $pid, $fileImporter, $this->debug !== false);
                $dbal = DBALFactory::getInstance();
                $persistedCount = $dbal->persistImportModels($groupedImportMappingModels);
                if (!empty($languageOverlays)) {
                    /** @var DataHandlerProcessor $dataHandlerProcessor */
                    $dataHandlerProcessor = GeneralUtility::makeInstance(DataHandlerProcessor::class);
                    $dataHandlerProcessor->processImportModels($groupedImportMappingModels, $pid, $languageOverlays, $this->debug !== false);
                }
                $fileImporter->runQueue();
                if ($fileImporter->hasUpdates()) {
                    $excludeFileReferenceUidList = $fileImporter->getExcludeFileReferenceUids();
                    $dbal->removeNotUpdatedObjects(FileReference::class, $dbImportSource, $pid, $importStartTimestamp, $excludeFileReferenceUidList);
                    $fileImporter->runStorageIndexing();
                }
                if ($persistedCount === 0 && !$fileImporter->hasUpdates()) {
                    $this->hasChangedData = false;
                }
                if ($this->debug) {
                    $apiConnector->deleteOldLocalCacheFiles();
                }
            } catch (\Throwable $e) {
                // Store exception so that it can be saved to the database
                $this->exceptions[] = $e;
            }
        }

        foreach ($apiConnector->getExceptions() as $exception) {
            $this->exceptions[] = $exception;
        }
        if ($this->debug && !empty($this->exceptions)) {
            /** @var \Throwable $exception */
            foreach ($this->exceptions as $exception) {
                echo $exception->getMessage() . ' [' . $exception->getFile() . '::' . $exception->getLine() . ']' . "\n";
            }
        }
        return $apiConnector->getSuccessfulResponseCount() > 0 || empty($this->exceptions);
    }

    /**
     * Since TYPO3 13.4 we need the request object to initialize the configuration manager for Extbase
     * @param int $targetPid
     * @return void
     */
    protected function initializeExtbaseEnvironment(int $targetPid): void
    {
        // TYPO3 >= 13: Initialize TYPO3_REQUEST, so Extbase can be used
        // @see \TYPO3\CMS\Extbase\Configuration\ConfigurationManager::getConfiguration
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
            $pageRecord = BackendUtility::getRecord('pages', $targetPid);
            if ($pageRecord) {
                $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                try {
                    $site = $siteFinder->getSiteByPageId($targetPid);
                    $request = $request->withAttribute('site', $site);
                } catch (SiteNotFoundException $e) {
                    unset($e);
                }
                $request = $request->withQueryParams(['id' => $targetPid]);
            }
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }
    }

    /**
     * Fetch the data
     *
     * @param ExtendedApiConnector $apiConnector The API connector
     * @param int $importStartTimestamp The timestamp when the import was started
     * @return bool Returns true if new or updated data were retrieved
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    protected function fetchDataFromApi(ExtendedApiConnector $apiConnector, int $importStartTimestamp): bool
    {
        $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($apiConnector->getBaseUri());
        // First, check if any event has changed. If not, we can stop the import
        // this is because each event contains the field "modifiedAtRecursive", which also changes if any related data
        // have been changed
        $mainImportType = 'Event';
        $itemListImportTypes = $this->enqueueItemsForType($apiConnector, $mainImportType, $dbImportSource, $importStartTimestamp);
        // If any of the main items has been updated, we have to check for changes in all other entry types
        // This prevents deletion of items that may currently not be referenced but are still active
        if (!empty($itemListImportTypes) && array_sum($itemListImportTypes) > 0) {
            $allImportTypes = $apiConnector->getApiImportTypes();
            foreach ($allImportTypes as $importType) {
                if ($importType !== $mainImportType) {
                    $this->enqueueItemsForType($apiConnector, $importType, $dbImportSource, $importStartTimestamp);
                }
            }
        } else {
            // No import necessary if no event has been changed
            return false;
        }
        $this->hasChangedData = true;
        return true;
    }

    /**
     * Enqueue all items (that need to be updated) for the given type
     * @param ExtendedApiConnector $apiConnector
     * @param string $importType
     * @param string $dbImportSource The import source
     * @param int $importStartTimestamp
     * @return array
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    protected function enqueueItemsForType(ExtendedApiConnector $apiConnector, string $importType, string $dbImportSource, int $importStartTimestamp): array
    {
        $allListItems = $apiConnector->fetchItemListForType($importType);
        $itemListImportTypes = [];
        foreach ($allListItems as $listItem) {
            $importType = $listItem['@type'];
            $importId = ExtendedApiConnector::filterId($listItem['@id'], $importType);
            $itemListImportTypes[$importId] = $this->addApiListItemToQueue($apiConnector, $importId, $importType, $listItem);
        }
        if (!empty($itemListImportTypes)) {
            $importIdList = array_keys($itemListImportTypes);
        } else {
            $importIdList = [];
        }
        $typeMapping = $apiConnector->getMappingForType($importType);
        $dbal = DBALFactory::getInstance();
        $dbal->fixImportSourceNames($typeMapping['table'], $apiConnector->getBaseUri());
        $dbal->processImportedItems($typeMapping['table'], $importIdList, $dbImportSource, $importStartTimestamp);
        return $itemListImportTypes;
    }

    /**
     * @param ExtendedApiConnector $apiConnector
     * @param int $importId
     * @param string $importModelType
     * @param array $listItem
     * @return int The import type
     */
    protected function addApiListItemToQueue(ExtendedApiConnector $apiConnector, int $importId, string $importModelType, array $listItem): int
    {
        $dataHash = md5(json_encode($listItem));
        $prevQueueItem = $this->getImportScheduleUtility()->fetchPreviousEntry($importId, $importModelType);
        $doImport = true;
        if (!empty($listItem['modifiedAtRecursive'])) {
            $modified = strtotime((string)$listItem['modifiedAtRecursive']);
        } elseif (!empty($listItem['modifiedAt'])) {
            $modified = strtotime((string)$listItem['modifiedAt']);
        } else {
            $modified = null;
        }
        // If no previous queue item exists, the item will be imported; Always import if the item has images or attachments
        if (!empty($prevQueueItem) && !in_array($importModelType, ['EventAttachment', 'EventImage', 'Sponsor'])) {
            if ($modified !== null) {
                $hasChangedSinceLastImport = $modified > $prevQueueItem['last_modified_tstamp'];
            } else {
                $hasChangedSinceLastImport = $prevQueueItem['data_hash'] !== $dataHash;
                if ($hasChangedSinceLastImport) {
                    $modified = time();
                } else {
                    $modified = $prevQueueItem['last_modified_tstamp'];
                }
            }
            $doImport = !$prevQueueItem['data_processed'] || $hasChangedSinceLastImport;
        }
        $itemImportType = ImportScheduleUtility::IMPORT_TYPE_NO_CHANGE;
        if ($doImport || $this->debug) {
            if ($apiConnector->listItemContainsAllData($importModelType)) {
                $apiResponse = $listItem;
            } elseif (!$this->debug || $this->debug === 'forceUpdate' || empty($prevQueueItem['import_data'])) {
                // Debug mode: Reuse the api data from the previous entry instead of making the api call,
                // but only if the data is not marked as changed anyway
                $apiResponse = $apiConnector->fetchRecordData($importId, $importModelType);
            } else {
                $apiResponse = json_decode((string)$prevQueueItem['import_data'], true);
                if (empty($apiResponse) || !is_array($apiResponse) || empty($apiResponse['@type'])) {
                    $apiResponse = $apiConnector->fetchRecordData($importId, $importModelType);
                }
            }
            if (empty($apiResponse)) {
                return $itemImportType;
            }
            if ($this->debug || empty($modified)) {
                $modified = time();
            }
            if (empty($prevQueueItem)) {
                $itemImportType = ImportScheduleUtility::IMPORT_TYPE_INSERT;
            } else {
                $itemImportType = ImportScheduleUtility::IMPORT_TYPE_UPDATE;
            }
            $this->getImportScheduleUtility()->saveQueueItem(
                $importId,
                $importModelType,
                $itemImportType,
                $apiResponse,
                $modified,
                $dataHash,
                $prevQueueItem
            );
        }
        return $itemImportType;
    }

    /**
     * @return bool
     */
    public function hasChangedData(): bool
    {
        return $this->hasChangedData;
    }

    /**
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    protected function getImportScheduleUtility(): ImportScheduleUtility
    {
        if ($this->importScheduleUtility === null) {
            $this->importScheduleUtility = GeneralUtility::makeInstance(ImportScheduleUtility::class);
        }
        return $this->importScheduleUtility;
    }
}
