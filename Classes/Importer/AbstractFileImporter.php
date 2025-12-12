<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2022 Brain Appeal GmbH
 *
 * @copyright 2022 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as FileReferenceModel;

abstract class AbstractFileImporter
{
    /**
     * File name prefix for temporary files
     */
    protected const TEMP_FILE_NAME_PREFIX = 'tx_campuseventsconnector_';
    /**
     * @var array
     */
    protected $newReferenceQueue;
    /**
     * @var array
     */
    protected $mappingOfUsedFileNamesToReferenceUid;

    /**
     * @var int[]
     */
    protected $updateReferenceIds;

    /**
     * @var ?\TYPO3\CMS\Core\Resource\Folder
     */
    protected $falFolder;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $storage;

    /**
     * @var int
     */
    protected $storageId;

    /**
     * @var string
     */
    protected $storageFolder;

    /**
     * @var array
     */
    protected $tempFiles = [];

    public function __construct()
    {
        $this->newReferenceQueue = [];
        $this->updateReferenceIds = [];
        $this->mappingOfUsedFileNamesToReferenceUid = [];

        $this->storageId = 0;
        $this->storageFolder = 'tx_campuseventsconnector/';
    }

    /**
     * @return DBALInterface
     */
    private function getDBAL()
    {
        $dbal = \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory::getInstance();

        return $dbal;
    }

    /**
     * @return null|\TYPO3\CMS\Core\Resource\ResourceStorage
     */
    private function getStorage()
    {
        if (null === $this->storage) {
            // for FAL storage
            /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
            $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);

            // use configured FAL storage
            if ($this->storageId > 0) {
                $storage = $resourceFactory->getStorageObject($this->storageId);
            } else {
                $storage = $resourceFactory->getDefaultStorage();
            }
            $this->storage = $storage;
        }

        return $this->storage;
    }

    /**
     * Returns an absolute file to a temporary file with unique file name
     *
     * @return string
     */
    final protected function getTempFilePath(): string
    {
        $tempFilenameAndPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam(self::TEMP_FILE_NAME_PREFIX);
        $this->tempFiles[] = $tempFilenameAndPath;
        return $tempFilenameAndPath;
    }

    /**
     * @return ?Folder
     */
    private function getFalFolder(): ?Folder
    {
        if (null === $this->falFolder && null !== $storage = $this->getStorage()) {
            $storageFolder = $this->storageFolder;
            try {
                if ($storage->hasFolder($storageFolder)) {
                    $falFolder = $storage->getFolder($storageFolder);
                } else {
                    $falFolder = $storage->createFolder($storageFolder);
                }
                $this->falFolder = $falFolder;
            } catch (\Throwable $e) {
                $this->falFolder = null;
            }
        }

        return $this->falFolder;
    }

    /**
     * @param string $sourcePath
     * @param string $targetFileName
     * @param int $importId
     * @param ImportedModelInterface $object
     * @param string $objectProperty
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    private function createAndAttachFile(string $sourcePath, string $targetFileName, $importId, $object, $objectProperty)
    {
        if ((null !== $falFolder = $this->getFalFolder()) && null !== $storage = $this->getStorage()) {
            // add file to FAL
            /** @var \TYPO3\CMS\Core\Resource\File $newFile */
            $newFile = $storage->addFile(
                $sourcePath,
                $falFolder,
                $targetFileName,
                \TYPO3\CMS\Core\Resource\DuplicationBehavior::REPLACE, // rename possible duplicates
                $deleteAfterAddingToFAL = true // remove $tempFilenameAndPath
            );

            $this->addFileReference($object, $newFile, $importId, $objectProperty);
            // Fallback for deleting temporary files
            $localFilePath = PathUtility::getCanonicalPath($sourcePath);
            if (file_exists($localFilePath)) {
                unlink($localFilePath);
            }
        }
    }

    /**
     * @param ImportedModelInterface $object
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param int $importId
     * @param string $property
     * @return void
     */
    private function addFileReference($object, $file, $importId, $property)
    {
        $attribs = [
            'ce_import_source' => $object->getCeImportSource(),
            'ce_import_id' => $importId,
            'ce_imported_at' => time(),
        ];
        $uidForeign = $object->getUid();
        $table = AbstractImportedRepository::getTableForModelClass($object::class);
        $storagePid = $object->getPid();
        $fileReferenceUid = $this->getDBAL()->addSysFileReference($file, $table, $storagePid, $uidForeign, $property, $attribs);
        if ($fileReferenceUid) {
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
        }
    }

    /**
     * Delete all previously imported files, that are not used anymore
     * @return array<string, int> List of deleted file names with deleted state information
     * @throws \Exception
     */
    private function cleanupFiles(): array
    {
        $fileDeleteStates = [];
        if (!empty($this->newReferenceQueue) && null !== $falFolder = $this->getFalFolder()) {
            try {
                $existingFiles = $falFolder->getFiles();
            } catch (InsufficientFolderReadPermissionsException) {
                $existingFiles = [];
            }
            $allUsedFileNames = array_keys($this->mappingOfUsedFileNamesToReferenceUid);
            $newlyAddedFileNames = [];
            foreach ($this->newReferenceQueue as $offset => $queueEntry) {
                $newlyAddedFileNames[$queueEntry['target_file_name']] = $offset;
                $allUsedFileNames[] = $queueEntry['target_file_name'];
            }
            foreach ($existingFiles as $file) {
                $fileDeleteStates[$file->getName()] = -1;
            }
            $dbal = DBALFactory::getInstance();
            foreach ($existingFiles as $file) {
                // Delete all the files, that are not used anymore
                if (!in_array($file->getName(), $allUsedFileNames, false)) {
                    try {
                        $dbal->deleteAllFileReferencesForFile($file);
                        $file->delete();
                        $fileDeleteStates[$file->getName()] = 1;
                    } catch (InsufficientFolderReadPermissionsException) {
                        $fileDeleteStates[$file->getName()] = -1;
                    }
                } elseif (isset($newlyAddedFileNames[$file->getName()])) {
                    $queueOffset = $newlyAddedFileNames[$file->getName()];
                    $queueEntry =& $this->newReferenceQueue[$queueOffset];
                    $queueEntry['file'] = $file;
                    $queueEntry['_file_size'] = (int)$file->getSize();
                    // Add randomness to update time, so files will not be updated at the same time
                    $minTstamp = time() - (7 + random_int(1, 5)) * 86400 + random_int(1, 86400);
                    $queueEntry['_modification_date'] = $file->getModificationTime();
                    if ($file->isMissing()) {
                        $queueEntry['file_exists'] = false;
                    } elseif (!empty($queueEntry['data']['size'])) {
                        $queueEntry['file_exists'] = (int)$file->getSize() === (int)$queueEntry['data']['size'];
                    } else {
                        $queueEntry['file_exists'] = $file->getModificationTime() > $minTstamp;
                    }
                    $queueEntry['_min_tstamp'] = $minTstamp;
                }
            }
        }
        return $fileDeleteStates;
    }

    public function deleteObsoleteFileReferenceOnImport(FileReferenceModel $fileReference): bool
    {
        try {
            $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
            $dbal = DBALFactory::getInstance();
            $dbal->deleteAllFileReferencesForFile($originalFile);
            $originalFile->delete();
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete all temporary files, that are not used anymore
     */
    private function cleanupTemporaryFilesAfterFinish(): void
    {
        // Delete all temporary files that were created during the current import process
        foreach ($this->tempFiles as $file) {
            if (file_exists($file) && is_writable($file)) {
                unlink($file);
            }
        }
        self::cleanupTemporaryFiles();
    }

    /**
     * Delete all previously generated temporary files
     *
     * @return void
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam
     */
    public static function cleanupTemporaryFiles()
    {
        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (is_dir($temporaryPath)) {
            $filePrefix = self::TEMP_FILE_NAME_PREFIX;
            $files = glob($temporaryPath . '/' . $filePrefix . '*');
            $threshold = strtotime('-1 week');
            foreach ($files as $file) {
                if (is_writable($file) && filemtime($file) < $threshold) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Creates and returns the sanitized file name to be used in the local storage
     *
     * @param int $importId
     * @param string $fileBaseName
     * @return ?string
     */
    protected function getImportFileName(int $importId, string $fileBaseName): ?string
    {
        $filename = str_pad($importId, 4, "0", STR_PAD_LEFT) . '-' . $fileBaseName;
        $falFolder = $this->getFalFolder();
        $storage = $this->getStorage();
        if ($falFolder !== null && $storage !== null) {
            return $storage->sanitizeFileName($filename, $falFolder);
        }
        return null;
    }

    /**
     * Add the file references for all new and updated files
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function runQueue(): void
    {
        if (!empty($this->newReferenceQueue)) {
            $this->cleanupFiles();
            foreach ($this->newReferenceQueue as $queueEntry) {
                /** @var ImportedModelInterface $object */
                $object = $queueEntry['object'];
                $importId = (int)$queueEntry['import_id'];
                $property = (string) $queueEntry['property'];

                if (!empty($queueEntry['file_exists'])) {
                    if (is_array($queueEntry['data']) && !empty($queueEntry['data']['name'])) {
                        $targetFileName = $queueEntry['data']['name'];
                    } else {
                        $targetFileName = $queueEntry['target_file_name'];
                    }
                    $existingReference = $this->getFileReferenceIfExists($object, $property, $targetFileName);
                    if (null === $existingReference && (($file = $queueEntry['file']) instanceof File)) {
                        $this->addFileReference($object, $file, $importId, $property);
                    }
                } else {
                    $downloadFile = $this->getDownloadFromQueueEntry($queueEntry);
                    if (!empty($downloadFile)) {
                        $filename = $queueEntry['target_file_name'];
                        $this->createAndAttachFile($downloadFile, $filename, $importId, $object, $property);
                    }
                }
            }
        }
        $this->cleanupTemporaryFilesAfterFinish();
    }

    /**
     * Returns true, if the importer has updated data
     * @return bool
     */
    public function hasUpdates(): bool
    {
        return !empty($this->newReferenceQueue);
    }

    public function runStorageIndexing(): void
    {
        if ($this->storageId > 0 && $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($this->storageId)) {
            $currentEvaluatePermissionsValue = $storage->getEvaluatePermissions();
            $storage->setEvaluatePermissions(false);
            $indexer = $this->getIndexer($storage);
            $indexer->processChangesInStorages();
            $storage->setEvaluatePermissions($currentEvaluatePermissionsValue);
        }
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

    /**
     * Returns the list of file reference IDs to be excluded from deletion
     * @return array<int<0,max>, int>|int[]
     */
    public function getExcludeFileReferenceUids(): array
    {
        return array_values($this->updateReferenceIds);
    }

    /**
     * Returns the existing file reference for the given object property file relation, if it already exists
     * @param ImportedModelInterface $object
     * @param string $property
     * @param string $targetFileName
     * @return FileReference|null
     */
    protected function getFileReferenceIfExists(ImportedModelInterface $object, string $property, string $targetFileName): ?FileReference
    {
        if ((int) $object->getUid() > 0) {
            if ($object->_hasProperty($property)) {
                $sanitizedPropertyName = $property;
            } else {
                // Convert to camel case
                $sanitizedPropertyName = lcfirst(str_replace('_', '', ucwords($property, '_')));
            }
            if ($object->_hasProperty($sanitizedPropertyName)) {
                $objectValue = $object->_getProperty($sanitizedPropertyName);
                if ($objectValue instanceof FileReference) {
                    return $objectValue;
                }
                if ($objectValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage || is_countable($objectValue)) {
                    foreach ($objectValue as $fileReference) {
                        if (($fileReference instanceof FileReference)
                            && $targetFileName === $fileReference->getOriginalResource()->getName()) {
                            return $fileReference;
                        }
                    }
                }
            }
        }

        return null;
    }


    /**
     * Returns the uid of the original file for the given file reference, if the file exists
     * @param FileReferenceModel $fileReference
     * @return bool
     */
    protected function originalResourceIsValid(FileReferenceModel $fileReference): bool
    {
        try {
            $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
        } catch (ResourceDoesNotExistException) {
            return false;
        }
        if ($originalFile->isMissing()) {
            return false;
        }
        return $originalFile->getStorage()->hasFile($originalFile->getIdentifier());
    }


    /**
     * @param array<string, mixed> $queueEntry
     * @return string|null
     */
    abstract protected function getDownloadFromQueueEntry(array $queueEntry): ?string;
}
