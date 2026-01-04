<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use Doctrine\DBAL\Exception;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\FileDownloadFailedException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileMappingModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileReferenceModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Utility\FileUtility;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File importer for import record images
 */
class FileWriter
{
    /**
     * Local cache for allowed file extension for given table and field names
     * @var array<string, string[]>
     */
    protected array $localCacheAllowedFileTypes = [];

    /**
     * Log messages
     * @var array<string, string[]>
     */
    private array $messages = [];

    private static bool $storageIndexUpdated = false;

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Processes a list of import models by loading current values, updating files as necessary,
     * and removing invalid file references. This method ensures minimal queries by grouping them
     * per data type or table.
     *
     * @param ImportDataTransformerInterface $dataTransformer The data transformer responsible for handling file processing and table-specific operations.
     * @param array<int, ImportRecordModel> $importModelList The list of import models to be processed.
     * @param string $targetFolderIdentifier Identifier for the target folder where files are managed.
     * @throws Exception|FolderDoesNotExistException If an error occurs during file or database processing.
     */
    public function processImportModelList(ImportDataTransformerInterface $dataTransformer, array $importModelList, string $targetFolderIdentifier): void
    {
        if (!$dataTransformer->hasFileTransformations()) {
            return;
        }
        // 1. Load the current values (if necessary), to check which files need to be updated
        // The goal is to have only 1 query per data type/table
        [$importFileModels, $recordIdListByLanguage] = $this->initializeImportData($importModelList, $dataTransformer);

        $this->processImportFileModelList($dataTransformer->getTable(), $importFileModels, $targetFolderIdentifier, $recordIdListByLanguage);
    }

    /**
     * Processes a list of import models by loading current values, updating files as necessary,
     * and removing invalid file references. This method ensures minimal queries by grouping them
     * per data type or table.
     *
     * @param string $tableName
     * @param array $importFileModels
     * @param string $targetFolderIdentifier Identifier for the target folder where files are managed.
     * @param array<int, int[]> $recordIdListByLanguage
     * @throws Exception If an error occurs during file or database processing.
     * @throws FolderDoesNotExistException If an error occurs during file or database processing.
     */
    public function processImportFileModelList(string $tableName, array $importFileModels, string $targetFolderIdentifier, array $recordIdListByLanguage = []): void
    {
        $fileReferences = $this->addFileReferenceDataToImportModels($tableName, $recordIdListByLanguage);
        if (!empty($importFileModels)) {
            $folder = $this->initializeFolder($targetFolderIdentifier);
            $this->assignFileReferencesToImportModels($importFileModels, $fileReferences);
            $this->processNewOrUpdatedFiles($folder, $importFileModels, $tableName);
        }
        $this->deleteInvalidFileReferences($fileReferences);
    }

    /**
     * Initializes the import data by preparing file models, record list, and fields.
     *
     * @param array<ImportRecordModel> $importModelList
     * @param ImportDataTransformerInterface $dataTransformer
     * @return array{0: ImportFileMappingModel[], 1: array<int, int[]>}
     */
    private function initializeImportData(array $importModelList, ImportDataTransformerInterface $dataTransformer): array
    {
        $importFileModels = [];
        $recordIdListByLanguage = [];
        foreach ($importModelList as $importModel) {
            $targetRecordId = $importModel->getTargetRecordId();
            if (!$targetRecordId) {
                continue;
            }
            $recordIdListByLanguage[$importModel->getLanguageUid()][$targetRecordId] = $targetRecordId;
            $importFileMappingList = $dataTransformer->getFileDataTransformerHelper()->getImportFileMapping($importModel);
            foreach ($importFileMappingList as $importFileMappingItem) {
                $importFileModels[] = $importFileMappingItem;
            }
        }
        return [$importFileModels, $recordIdListByLanguage];
    }

    /**
     * Processes new or updated files by iterating the provided import file mapping items
     * and determining the appropriate action to take, such as checking if an update is needed
     * or fetching and saving files. The function interacts with file reference models and executes
     * corresponding logic based on the process type defined in the mapping item.
     *
     * @param Folder $folder The folder instance representing the target directory for file processing.
     * @param array<int, ImportFileMappingModel> $importFileModels List of import file mapping items to process.
     * @param string $tableName The name of the database table associated with the file mappings.
     */
    private function processNewOrUpdatedFiles(Folder $folder, array $importFileModels, string $tableName): void
    {
        foreach ($importFileModels as $importFileMappingItem) {
            $processType = $importFileMappingItem->getProcessType();
            switch ($processType) {
                case ImportFileMappingModel::PROCESS_TYPE_CHECK_UPDATE_LOCAL:
                    $this->checkUpdateNeededForImportModel($folder, $importFileMappingItem, $tableName);
                    break;
                case ImportFileMappingModel::PROCESS_TYPE_FETCH_REMOTE:
                    $this->saveFileForImportModel($folder, $importFileMappingItem, $tableName);
                    break;
            }
        }
    }

    /**
     * Handles the deletion of a file and its associated file reference.
     * If the deletion fails, logs an error with detailed information.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The file reference model containing details about the file
     *                                                     and its relationship to entities in the system.
     */
    private function handleFileDeletionForItem(ImportFileReferenceModel $fileReferenceModel): void
    {
        try {
            // Only delete the file reference if the file
            if (!FileUtility::deleteFileAndFileReference($fileReferenceModel)) {
                $this->log(sprintf('Deleting file %d failed: %s', $fileReferenceModel->getUidLocal(), $fileReferenceModel->getIdentifier()));
            }
        } catch (Exception $e) {
            $this->log(
                sprintf(
                    'Deleting file reference %d failed for %s:%d: %s',
                    $fileReferenceModel->getUidLocal(),
                    $fileReferenceModel->getTablenames(),
                    $fileReferenceModel->getUidForeign(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Updates the file metadata for a given file reference if the alternative text has changed.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The model containing information about the file reference.
     * @param ImportFileMappingModel $importFileMappingItem The mapping item containing the updated metadata.
     */
    private function updateFileMetadataIfNeeded(
        ImportFileReferenceModel $fileReferenceModel,
        ImportFileMappingModel $importFileMappingItem,
        bool $updateFileReference = false
    ): void {
        $persistedAlternative = $fileReferenceModel->getAlternative();
        $currentAlternative = $importFileMappingItem->getAlternative();

        if ($currentAlternative !== $persistedAlternative) {
            if ($updateFileReference) {
                $fileRefUid = $fileReferenceModel->getUid();
                if (!$fileRefUid) {
                    return;
                }
                $refTable = 'sys_file_reference';
                /** @var ConnectionPool $connectionPool */
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $queryBuilder = $connectionPool->getQueryBuilderForTable($refTable);
                $queryBuilder->update($refTable)
                    ->set('alternative', $currentAlternative)
                    ->where($queryBuilder->expr()->eq('uid', $fileReferenceModel->getUid()));
                return;
            }
            $sysFileUid = $fileReferenceModel->getUidLocal();
            try {
                $fileToUpdateMeta = $this->resourceFactory->getFileObject($sysFileUid); // File should exist here
                FileUtility::updateFileMetaData($fileToUpdateMeta, $importFileMappingItem->getMetaData());
                $this->logger->info(sprintf(
                    'Updated metadata for file UID %d (target ID %d, target field %s).',
                    $sysFileUid,
                    $importFileMappingItem->getTargetRecordId(),
                    $importFileMappingItem->getTargetField()
                ));
            } catch (FileDoesNotExistException) {
                $this->log(sprintf(
                    'File UID %d (target ID %d, target field %s) disappeared before metadata could be updated.',
                    $sysFileUid,
                    $importFileMappingItem->getTargetRecordId(),
                    $importFileMappingItem->getTargetField()
                ));
            }
        }
    }

    /**
     * Handles an existing file with timestamp checks to determine whether content
     * or metadata updates are necessary. Updates the file or logs a warning if
     * an update cannot be performed due to missing source URI.
     *
     * @param Folder $folder The folder where the file resides.
     * @param ImportFileMappingModel $importFileMappingItem The mapping model containing file information and timestamp.
     * @param string $tableName The name of the database table associated with the file.
     * @param ImportFileReferenceModel $fileReferenceModel The reference model for the file being processed.
     */
    private function handleExistingFileWithTimestampChecks(
        Folder $folder,
        ImportFileMappingModel $importFileMappingItem,
        string $tableName,
        ImportFileReferenceModel $fileReferenceModel
    ): void {
        $sysFileUid = $fileReferenceModel->getUidLocal();
        // We know $importFileMappingItem->hasTimestamp() is true here.
        $timestamp = $importFileMappingItem->getTimestamp();
        if ($this->isContentOutOfDate($fileReferenceModel, $timestamp, $importFileMappingItem)) {
            if ($importFileMappingItem->hasUri()) {
                $this->saveFileForImportModel($folder, $importFileMappingItem, $tableName);
            } else {
                $this->logger->warning(sprintf(
                    'Content update needed for local file UID %d (target ID %s, target field %s), but no source URI is available.',
                    $sysFileUid,
                    $importFileMappingItem->getTargetRecordId(),
                    $importFileMappingItem->getTargetField()
                ));
            }
            return; // Processed content update or logged warning.
        }
        $fileReferenceModel->setValid(true);
        // Content is up to date, now check metadata.
        $this->updateFileMetadataIfNeeded($fileReferenceModel, $importFileMappingItem);
    }

    /**
     * Determines if the content associated with a file reference is out of date.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The file reference model containing metadata about the file.
     * @param int $remoteTimestamp The timestamp of the remote file for comparison.
     * @param ImportFileMappingModel $importFileMappingItem The import mapping entry associated with the file reference.
     * @return bool True if the content is out of date, false otherwise.
     */
    private function isContentOutOfDate(
        ImportFileReferenceModel $fileReferenceModel,
        int $remoteTimestamp,
        ImportFileMappingModel $importFileMappingItem
    ): bool {
        $sysFileUid = $fileReferenceModel->getUidLocal();
        try {
            $fileObject = $this->resourceFactory->getFileObject($sysFileUid);
            // Content is out of date if the local modification time is older than the remote timestamp.
            return $fileObject->isDeleted() || $fileObject->isMissing() || $fileObject->getModificationTime() < $remoteTimestamp;
        } catch (FileDoesNotExistException) {
            $this->logger->info(sprintf(
                'Local file UID %d for import item (target ID %d, target field %s) not found. Will attempt to fetch if URI exists.',
                $sysFileUid,
                $importFileMappingItem->getTargetRecordId(),
                $importFileMappingItem->getTargetField()
            ));
            return true; // Missing file is considered out of date.
        }
    }

    /**
     * Check if a file needs to be updated for a single import model
     *
     * @param Folder $folder
     * @param ImportFileMappingModel $importFileMappingItem
     *         Mapping info for a file reference (field name, table, target record UID)
     * @param string $tableName The name of the table for the file reference
     */
    private function checkUpdateNeededForImportModel(Folder $folder, ImportFileMappingModel $importFileMappingItem, string $tableName): void
    {
        $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();

        // Guard: If there's no URI to fetch from AND no existing file reference to check/update.
        if (!$fileReferenceModel && !$importFileMappingItem->hasUri()) {
            $this->logger->debug(sprintf(
                'Skipping file check for import item (target ID %d, target field %s): No URI and no existing file reference.',
                $importFileMappingItem->getTargetRecordId(),
                $importFileMappingItem->getTargetField()
            ));
            return;
        }

        // Case 1: We have an existing file reference and a timestamp to check for content updates.
        if ($fileReferenceModel && $importFileMappingItem->hasTimestamp()) {
            $this->handleExistingFileWithTimestampChecks($folder, $importFileMappingItem, $tableName, $fileReferenceModel);
            return; // All necessary actions for this case are handled within the helper method.
        }

        // Case 2: Conditions for Case 1 are not met.
        // This means either:
        //  a) No $fileReferenceModel (no local file yet).
        //  b) $fileReferenceModel exists, BUT $importFileMappingItem->hasTimestamp() is false (cannot compare dates).
        // In either of these subcases, if a URI is available, we should try to fetch/save the file.
        if ($importFileMappingItem->hasUri()) {
            $this->saveFileForImportModel($folder, $importFileMappingItem, $tableName);
        }
        // If $fileReferenceModel exists, but no timestamp and no URI, nothing further is done for this item,
        // which matches the original logic's implicit behavior.
    }

    /**
     * Handles the saving or updating of a file for a specific import model.
     * Downloads a file to the FAL storage, associates it with the given import model,
     * and updates or creates a file reference in the database.
     *
     * @param Folder $folder The FAL folder where the file will be stored.
     * @param ImportFileMappingModel $importFileMappingItem Mapping info for a file reference (field name, table, target record UID)
     * @param string $tableName The name of the table for the file reference
     */
    private function saveFileForImportModel(Folder $folder, ImportFileMappingModel $importFileMappingItem, string $tableName): void
    {
        if ($importFileMappingItem->hasUri()) {
            $uri = $importFileMappingItem->getUri();
            $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();
            // File references are translated automatically by the DataHandler, so we only need to check the meta-data
            if ($fileReferenceModel && $fileReferenceModel->getLanguageUid() > 0) {
                // Content is up to date, now check metadata.sys_file_reference
                $this->updateFileMetadataIfNeeded($fileReferenceModel, $importFileMappingItem, true);
                $fileReferenceModel->setValid(true);
            }
            $targetField = $importFileMappingItem->getTargetField();
            $targetRecordId = $importFileMappingItem->getTargetRecordId();
            // A local file already exists
            if ($fileReferenceModel && $fileName = $fileReferenceModel->getFileName()) {
                $lastDotPos = strrpos($fileName, '.');
                $targetFileName = substr($fileName, 0, $lastDotPos);
            } else {
                $fileReferenceModel = new ImportFileReferenceModel();
                $hash = hash('sha1', $tableName . '-' . $targetField . '- ' . $targetRecordId);
                $targetFileName = $importFileMappingItem->getFileName() . '_' . $hash;
            }
            $allowedExtensions = $this->getAllowedFileExtensions($tableName, $targetField);
            try {
                $file = FileUtility::downloadFileToFal(
                    $folder,
                    $uri,
                    $targetFileName,
                    $importFileMappingItem->getMetaData(),
                    $allowedExtensions,
                    $importFileMappingItem->getClientOptions()
                );
                $fileReferenceModel->setTablenames($tableName);
                $fileReferenceModel->setFieldname($targetField);
                $fileReferenceModel->setUidLocal($file->getUid());
                $fileReferenceModel->setUidForeign($targetRecordId);
                $fileReferenceModel->setPid($importFileMappingItem->getPid());
                FileUtility::saveFileReference($fileReferenceModel);
                $fileReferenceModel->setValid(true);
            } catch (FileDownloadFailedException $e) {
                if ($e->getKeepExistingFile()) {
                    $fileReferenceModel->setValid(true);
                }
                $this->log(sprintf('Creating the file reference failed for %s: %s', $uri, $e->getMessage()));
            } catch (\Throwable $e) {
                // Keep the files for unknown exceptions
                $fileReferenceModel->setValid(true);
                $this->log(sprintf('Creating the file reference failed for %s: %s', $uri, $e->getMessage()));
            }
        }
    }

    /**
     * Initializes a folder based on the given target folder identifier. If the folder does not exist,
     * it will attempt to create the folder. Handles errors and logs issues during the process.
     *
     * @param string $targetFolderIdentifier The identifier of the target folder to initialize.
     * @return Folder The initialized or newly created folder object.
     * @throws FolderDoesNotExistException If the folder cannot be found or created.
     * @throws \Exception If any unexpected error occurs during the folder initialization.
     */
    protected function initializeFolder(string $targetFolderIdentifier): Folder
    {
        try {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($targetFolderIdentifier);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $e) {
            $parts = GeneralUtility::trimExplode(':', $targetFolderIdentifier);
            if (count($parts) === 2) {
                /** @var StorageRepository $storageRepository */
                $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
                $storage = $storageRepository->findByCombinedIdentifier($targetFolderIdentifier);
                /** @noinspection NullPointerExceptionInspection */
                $folder = $storage->createFolder($parts[1]);
            } else {
                $this->log(sprintf('The target folder identifier %s is invalid', $targetFolderIdentifier));
                throw $e;
            }
        } catch (\Exception $e) {
            $this->log(sprintf('Initializing the target folder failed for %s: %s', $targetFolderIdentifier, $e->getMessage()));
            throw $e;
        }
        if (!self::$storageIndexUpdated) {
            try {
                $this->runStorageIndexing($folder->getStorage());
            } catch (\Exception $e) {
                $this->log(sprintf('Storage indexing failed for %d while initializing the folder %s. Message: %s',
                    $targetFolderIdentifier,
                    $folder->getStorage()->getUid(),
                    $e->getMessage()
                ));
            }
        }
        return $folder;
    }

    /**
     * Adds file reference data to the provided import models.
     *
     * This method retrieves file references for the specified table and records the ID list
     * and updates the corresponding import models with the retrieved file reference data.
     *
     * @param string $table The name of the table to retrieve file reference data from.
     * @param array<int, int[]> $recordIdListByLanguage Optional list of record id's to limit loading of file references
     * @return array<string, ImportFileReferenceModel[]> Existing file references
     * @throws Exception
     */
    private function addFileReferenceDataToImportModels(string $table, array $recordIdListByLanguage = []): array
    {
        $fileReferencesByField = [];
        foreach ($recordIdListByLanguage as $languageUid => $recordIdList) {
            $fileRefResult = FileUtility::getFileReferencesForTableRecordList($table, $recordIdList, $languageUid);
            while ($refRow = $fileRefResult->fetchAssociative()) {
                $fileReferenceModel = new ImportFileReferenceModel($refRow);
                $targetRecordId = $fileReferenceModel->getUidForeign();
                $key = $targetRecordId . '_' . $fileReferenceModel->getFieldname() . '_' . $fileReferenceModel->getLanguageUid();
                if (!isset($fileReferencesByField[$key])) {
                    $fileReferencesByField[$key] = [];
                }
                $fileReferencesByField[$key][] = $fileReferenceModel;
            }
            $fileRefResult->free();
        }
        return $fileReferencesByField;
    }

    /**
     * @param array<string, ImportFileReferenceModel[]> $fileReferencesByField
     */
    private function deleteInvalidFileReferences(array $fileReferencesByField): void
    {
        foreach ($fileReferencesByField as $fileReferences) {
            foreach ($fileReferences as $fileReference) {
                if (!$fileReference->isValid()) {
                    $this->handleFileDeletionForItem($fileReference);
                }
            }
        }
    }

    /**
     * @param ImportFileMappingModel[] $importFileModels
     * @param array<string, ImportFileReferenceModel[]> $fileReferencesByField
     */
    private function assignFileReferencesToImportModels(array $importFileModels, array $fileReferencesByField): void
    {
        foreach ($importFileModels as $model) {
            $key = $model->getTargetRecordId() . '_' . $model->getTargetField() . '_' . $model->getLanguageUid();
            if (!empty($fileReferencesByField[$key])) {
                // Get the first key of the array
                $keys = array_keys($fileReferencesByField[$key]);
                $firstKey = $keys[0];
                $fileReference = $fileReferencesByField[$key][$firstKey];
                $model->setFileReferenceModel($fileReference);
                // Unset only the first entry without reindexing the array
                unset($fileReferencesByField[$key][$firstKey]);
            }
        }
    }

    /**
     * Returns the list of allowed file extensions for the given table and field if any restrictions are defined
     *
     * @return string[]
     */
    protected function getAllowedFileExtensions(string $table, string $field): array
    {
        $cacheKey = $table . '-' . $field;
        if (!array_key_exists($cacheKey, $this->localCacheAllowedFileTypes)) {
            $this->localCacheAllowedFileTypes[$cacheKey] = FileUtility::getAllowedFileExtensionsForTableAndField($table, $field);
        }
        return $this->localCacheAllowedFileTypes[$cacheKey];
    }

    /**
     * Retrieves error messages logged at the error level.
     *
     * @return array<string> A list of error messages.
     */
    public function getErrorMessages(): array
    {
        return $this->messages[LogLevel::ERROR] ?? [];
    }

    /**
     * Logs a message with the specified log level.
     *
     * @param string $message The message to be logged.
     * @param string $logLevel The logging level, defaults to LogLevel::ERROR.
     * @return void
     */
    private function log(string $message, string $logLevel = LogLevel::ERROR): void
    {
        if (!isset($this->messages[$logLevel])) {
            $this->messages[$logLevel] = [];
        }
        $this->messages[$logLevel][] = $message;
        $this->logger->log($logLevel, $message);
    }

    /**
     * Runs the storage indexing for the specified storage ID.
     *
     * @param ResourceStorage $storage The resource storage to index.
     * @return void
     */
    public function runStorageIndexing(ResourceStorage $storage): void
    {
        // Only run this once
        if (self::$storageIndexUpdated){
            return;
        }
        $currentEvaluatePermissionsValue = $storage->getEvaluatePermissions();
        $storage->setEvaluatePermissions(false);
        $indexer = $this->getIndexer($storage);
        $indexer->processChangesInStorages();
        $storage->setEvaluatePermissions($currentEvaluatePermissionsValue);
        self::$storageIndexUpdated = true;
    }

    /**
     * Gets the indexer
     *
     * @return \TYPO3\CMS\Core\Resource\Index\Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}
