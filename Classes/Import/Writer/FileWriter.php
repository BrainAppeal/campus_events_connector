<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\FileDownloadFailedException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileMappingModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileReferenceModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Repository\FileReferenceRepository;
use BrainAppeal\CampusEventsConnector\Import\Repository\FileRepository;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * File importer for import record images
 */
class FileWriter
{
    /**
     * Prevent downloading the same file multiple times for the same URI.
     * @var array<string, int>
     */
    protected array $mapFileUidByUri = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FileRepository $fileRepository,
        private readonly FileReferenceRepository $fileReferenceRepository
    )
    {
    }

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
        $importFileModels = $dataTransformer->getImportFileMappingModels($importModelList);

        $this->processImportFileModelList($importFileModels, $targetFolderIdentifier);
    }

    /**
     * Processes a list of import models by loading current values, updating files as necessary,
     * and removing invalid file references.
     *
     * @param ImportFileMappingModel[] $importFileModels
     * @param string $targetFolderIdentifier Identifier for the target folder where files are managed.
     * @throws Exception If an error occurs during file or database processing.
     * @throws FolderDoesNotExistException If an error occurs during file or database processing.
     */
    public function processImportFileModelList(array $importFileModels, string $targetFolderIdentifier): void
    {
        $fileReferences = $this->addFileReferenceDataToImportModels($importFileModels);
        if (!empty($importFileModels)) {
            $folder = $this->fileRepository->getFolderByIdentifier($targetFolderIdentifier);
            $this->assignFileReferencesToImportModels($importFileModels, $fileReferences);
            foreach ($importFileModels as $importFileMappingItem) {
                $this->saveOrUpdateImportModel($folder, $importFileMappingItem);
            }
        }
        $this->deleteInvalidFileReferences($fileReferences);
    }

    /**
     * Updates the file metadata for a given file reference if the alternative text has changed.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The model containing information about the file reference.
     * @param ImportFileMappingModel $importFileMappingItem The mapping item containing the updated metadata.
     */
    private function updateFileMetadataIfNeeded(
        ImportFileReferenceModel $fileReferenceModel,
        ImportFileMappingModel   $importFileMappingItem
    ): void {
        $sysFileUid = $fileReferenceModel->getUidLocal();
        $fileToUpdateMeta = $this->fileRepository->getValidFileOrNull($sysFileUid);
        if ($fileToUpdateMeta !== null) {
            $this->fileRepository->updateFileMetaData($fileToUpdateMeta, $importFileMappingItem->getMetaData(), $importFileMappingItem->getCategoryUidList());
            $this->logger->info(sprintf(
                'Updated metadata for file UID %d (target ID %d, target field %s).',
                $sysFileUid,
                $importFileMappingItem->getTargetRecordId(),
                $importFileMappingItem->getTargetField()
            ));
        } else {
            $this->logger->error(sprintf(
                'File UID %d (target ID %d, target field %s) disappeared before metadata could be updated.',
                $sysFileUid,
                $importFileMappingItem->getTargetRecordId(),
                $importFileMappingItem->getTargetField()
            ));
        }
    }

    /**
     * Handles an existing file with timestamp checks to determine whether content
     * or metadata updates are necessary. Updates the file or logs a warning if
     * an update cannot be performed due to missing source URI.
     *
     * @param Folder $folder The folder where the file resides.
     * @param ImportFileMappingModel $importFileMappingItem The mapping model containing file information and timestamp.
     */
    private function handleExistingFileWithTimestampChecks(
        Folder                 $folder,
        ImportFileMappingModel $importFileMappingItem
    ): void
    {
        if ($this->isContentOutOfDate($importFileMappingItem)) {
            if ($importFileMappingItem->hasUri()) {
                $this->saveFileForImportModel($folder, $importFileMappingItem);
            } else {
                $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();
                $sysFileUid = $fileReferenceModel ? $fileReferenceModel->getUidLocal() : 0;
                $this->logger->warning(sprintf(
                    'Content update needed for local file UID %d (target ID %s, target field %s), but no source URI is available.',
                    $sysFileUid,
                    $importFileMappingItem->getTargetRecordId(),
                    $importFileMappingItem->getTargetField()
                ));
            }
            return; // Processed content update or logged warning.
        }
        $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();
        if ($fileReferenceModel) {
            $fileReferenceModel->setValid(true);
            // Content is up to date, now check metadata.
            $this->updateFileMetadataIfNeeded($fileReferenceModel, $importFileMappingItem);
        }
    }

    /**
     * Determines if the content associated with a file reference is out of date.
     *
     * @param ImportFileMappingModel $importFileMappingItem The import mapping entry associated with the file reference.
     * @return bool True if the content is out of date, false otherwise.
     */
    private function isContentOutOfDate(
        ImportFileMappingModel $importFileMappingItem
    ): bool
    {
        if ($importFileMappingItem->isForceUpdate()) {
            return true;
        }
        $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();
        if (!$fileReferenceModel) {
            return true;
        }
        $sysFileUid = $fileReferenceModel->getUidLocal();
        $fileObject = $this->fileRepository->getValidFileOrNull($sysFileUid);
        return $fileObject === null || $fileObject->getModificationTime() < $importFileMappingItem->getLastUpdated();
    }

    /**
     * Check if a file needs to be updated for a single import model
     *
     * @param Folder $folder
     * @param ImportFileMappingModel $importFileMappingItem
     *         Mapping info for a file reference (field name, table, target record UID)
     */
    private function saveOrUpdateImportModel(Folder $folder, ImportFileMappingModel $importFileMappingItem): void
    {
        if (!$importFileMappingItem->hasUri()) {
            // If the file reference does not have a URI, it will be deleted later
            return;
        }

        // Case 1: We have an existing file reference and a timestamp to check for content updates.
        if ($importFileMappingItem->hasExistingFileReference() && $importFileMappingItem->hasTimestamp()) {
            $this->handleExistingFileWithTimestampChecks($folder, $importFileMappingItem);
            return; // All necessary actions for this case are handled within the helper method.
        }

        // Case 2: Conditions for Case 1 are not met.
        // This means either:
        //  a) No $fileReferenceModel (no local file yet).
        //  b) $fileReferenceModel exists, BUT $importFileMappingItem->hasTimestamp() is false (cannot compare dates).
        // In either of these subcases, if a URI is available, we should try to fetch/save the file.
        $this->saveFileForImportModel($folder, $importFileMappingItem);
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
     */
    private function saveFileForImportModel(Folder $folder, ImportFileMappingModel $importFileMappingItem): void
    {
        if (!$importFileMappingItem->hasUri()) {
            return;
        }
        $fileReferenceModel = $importFileMappingItem->getFileReferenceModel();
        $uri = $importFileMappingItem->getUri();
        $tableName = $importFileMappingItem->getTargetTable();
        $targetField = $importFileMappingItem->getTargetField();
        $targetRecordId = $importFileMappingItem->getTargetRecordId();
        $targetFileName = $importFileMappingItem->getFileName();
        $allowedExtensions = $this->fileRepository->getAllowedFileExtensionsForTableAndField($tableName, $targetField);
        $this->logger->info(sprintf('Downloading file %s for %s.%s:%d', $uri, $tableName, $targetField, $targetRecordId));
        try {
            if ($importFileMappingItem->isLocal()) {
                $tempFile = $importFileMappingItem->getUri();
                $fileReferenceModel = $importFileMappingItem->getSynchronizedFileReferenceModel();
                $file = $this->fileRepository->saveTempFileToFal(
                    $folder,
                    $tempFile,
                    $targetFileName,
                    $fileReferenceModel->getUidLocal(),
                );
            } else {
                $fileUidByUri = $this->mapFileUidByUri[$uri] ?? null;
                // don't download the same file multiple times for the same URI
                $file = $this->fileRepository->getValidFileOrNull((int)$fileUidByUri);
                $fileReferenceModel = $importFileMappingItem->getSynchronizedFileReferenceModel();
                if ($file === null) {
                    $downloadResult = $this->fileRepository->downloadFile($uri, $allowedExtensions, $importFileMappingItem->getClientOptions());
                    $tempFile = $downloadResult['file'];
                    $extension = $downloadResult['extension'];
                    if (($lastDotPos = (int)strrpos($targetFileName, '.')) > 0) {
                        $targetFileName = substr($targetFileName, 0, $lastDotPos);
                    }
                    // A local file already exists
                    $hash = hash('sha1', $uri);
                    $targetFileName .= '_' . $hash . '.' . $extension;
                    $this->logger->info(sprintf('File downloaded to temporary file %s (Size: %s). Target file name %s', $tempFile, filesize($tempFile), $targetFileName));
                    $file = $this->fileRepository->saveTempFileToFal(
                        $folder,
                        $tempFile,
                        $targetFileName,
                        $fileReferenceModel->getUidLocal(),
                    );
                    $this->mapFileUidByUri[$uri] = $file->getUid();
                }
            }
            $metaData = $importFileMappingItem->getMetaData();
            if (!empty($metaData['alternativeText'])) {
                $fileReferenceModel->setAlternative($metaData['alternativeText']);
            }
            $fileReferenceModel->setUidLocal($file->getUid());
            $this->fileRepository->updateFileMetaData($file, $importFileMappingItem->getMetaData(), $importFileMappingItem->getCategoryUidList());
            if ($this->fileReferenceRepository->saveFileReference($fileReferenceModel)) {
                $this->logger->info(sprintf('File reference saved: %s.%s. Record UID: %s; File: %s (UID: %d)', $tableName, $targetField, $targetRecordId, $file->getIdentifier(), $file->getUid()));
                $fileReferenceModel->setValid(true);
            } else {
                $this->logger->error(sprintf('Error when saving file reference for %s: %s.%s: %d', $uri, $tableName, $targetField, $targetRecordId));
            }
        } catch (FileDownloadFailedException $e) {
            if ($e->getKeepExistingFile() && $fileReferenceModel && $fileReferenceModel->getUid()
                && $this->fileRepository->getValidFileOrNull($fileReferenceModel->getUidLocal()) !== null) {
                $fileReferenceModel->setValid(true);
            }
            $this->logger->error(sprintf('Creating the file reference failed for %s: %s', $uri, $e->getMessage()));
        } catch (\Throwable $e) {
            // Keep the files for unknown exceptions
            if ($fileReferenceModel && $fileReferenceModel->getUid()
                && $this->fileRepository->getValidFileOrNull($fileReferenceModel->getUidLocal()) !== null) {
                $fileReferenceModel->setValid(true);
            }
            $this->logger->error(sprintf('Saving the file or file reference failed for %s: %s (%s:%s; %s)', $uri, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
        }
    }

    /**
     * Adds file reference data to the provided import models.
     *
     * This method retrieves file references for the specified table and records the ID list
     * and updates the corresponding import models with the retrieved file reference data.
     *
     * @param ImportFileMappingModel[] $importFileModels The import file models
     * @return array<string, ImportFileReferenceModel[]> Existing file references
     * @throws Exception
     */
    private function addFileReferenceDataToImportModels(array $importFileModels): array
    {
        $recordIdListByTableAndLanguage = [];
        foreach ($importFileModels as $importFileModel) {
            $targetRecordId = $importFileModel->getTargetRecordId();
            $targetTable = $importFileModel->getTargetTable();
            $recordIdListByTableAndLanguage[$targetTable][$importFileModel->getLanguageUid()][$targetRecordId] = $targetRecordId;
        }
        $fileReferencesByField = [];
        foreach ($recordIdListByTableAndLanguage as $table => $recordIdListByLanguage) {
            foreach ($recordIdListByLanguage as $languageUid => $recordIdList) {
                $fileRefResult = $this->fileReferenceRepository->getFileReferencesForTableRecordList($table, $recordIdList, $languageUid);
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
                    $this->fileReferenceRepository->deleteFileAndFileReference($fileReference);
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
     * Checks the consistency of file references for a given table and target folder identifier.
     *
     * @param string $table The name of the database table to process.
     * @param string $targetFolderIdentifier The identifier of the target folder to initialize and check.
     * @return bool Returns true if all file references are valid; otherwise, false.
     */
    public function checkConsistency(string $table, string $targetFolderIdentifier): bool
    {
        $folderIsValid = $this->fileRepository->checkTargetFolderIsAccessible($targetFolderIdentifier);
        $fileReferencesAreValid = $this->fileReferenceRepository->checkConsistency($table);
        return $folderIsValid && $fileReferencesAreValid;
    }

    /**
     * Handles operations to be performed after the import process is finished.
     * This forces the indexer to update the storage index if it has been modified during the import.
     *
     * @param string $targetFolderIdentifier The identifier of the target folder where the import was completed.
     * @return void
     */
    public function onImportFinish(string $targetFolderIdentifier): void
    {
        $folder = $this->fileRepository->checkTargetFolderIsAccessible($targetFolderIdentifier);
        if ($folder) {
            $this->fileReferenceRepository->checkFileReferencesInFolder($folder);
        }
    }
}
