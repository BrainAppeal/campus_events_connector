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
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

abstract class AbstractFileImporter
{
    /**
     * @var array
     */
    protected $newReferenceQueue;

    /**
     * @var int[]
     */
    protected $updateReferenceIds;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder
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

    public function __construct()
    {
        $this->newReferenceQueue = [];
        $this->updateReferenceIds = [];

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
     * @return \TYPO3\CMS\Core\Resource\ResourceStorage
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
     * @return \TYPO3\CMS\Core\Resource\Folder|\TYPO3\CMS\Core\Resource\InaccessibleFolder
     */
    private function getFalFolder()
    {
        if (null === $this->falFolder) {
            $storage = $this->getStorage();
            $storageFolder = $this->storageFolder;
            if ($storage->hasFolder($storageFolder)) {
                $falFolder = $storage->getFolder($storageFolder);
            } else {
                $falFolder = $storage->createFolder($storageFolder);
            }

            $this->falFolder = $falFolder;
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
    private function createAndAttachFile($sourcePath, $targetFileName, $importId, $object, $objectProperty)
    {
        $falFolder = $this->getFalFolder();

        // add file to FAL
        /** @var \TYPO3\CMS\Core\Resource\File $newFile */
        $newFile = $this->getStorage()->addFile(
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
        $fileReferenceUid = $this->getDBAL()->addSysFileReference($file, $object, $property, $attribs);
        if ($fileReferenceUid) {
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
        }
    }

    /**
     * Delete all previously imported files, that are not used anymore
     * @return array List of deleted file names with deleted state information
     * @throws \Exception
     */
    private function cleanupFiles(): array
    {
        $fileDeleteStates = [];
        if (!empty($this->newReferenceQueue)) {
            $falFolder = $this->getFalFolder();
            try {
                $existingFiles = $falFolder->getFiles();
            } catch (InsufficientFolderReadPermissionsException $e) {
                $existingFiles = [];
            }
            $activeFileNames = [];
            foreach ($this->newReferenceQueue as $offset => $queueEntry) {
                $activeFileNames[$queueEntry['target_file_name']] = $offset;
            }
            foreach ($existingFiles as $file) {
                // Delete all files, that are not used anymore
                if (!isset($activeFileNames[$file->getName()])) {
                    try {
                        $file->delete();
                        $fileDeleteStates[$file->getName()] = 1;
                    } catch (InsufficientFolderReadPermissionsException $e) {
                        $fileDeleteStates[$file->getName()] = -1;
                    }
                } else {
                    $queueOffset = $activeFileNames[$file->getName()];
                    $queueEntry =& $this->newReferenceQueue[$queueOffset];
                    $queueEntry['file'] = $file;
                    $queueEntry['_file_size'] = (int)$file->getSize();
                    // Add randomness to update time, so files will not be updated at the same time
                    $minTstamp = time() - (7 + random_int(1, 5)) * 86400 + random_int(1, 86400);
                    $queueEntry['_modification_date'] = $file->getModificationTime();
                    if (!empty($queueEntry['data']['size'])) {
                        $queueEntry['file_exists'] = (int)$file->getSize() === (int)$queueEntry['data']['size'];
                    } else {
                        $queueEntry['file_exists'] = $file->getModificationTime() > $minTstamp;
                    }
                    $queueEntry['_min_tstamp'] = $minTstamp;
                }
            }
        }
        self::cleanupTemporaryFiles();
        return $fileDeleteStates;
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
            $filePrefix = 'tx_campuseventsconnector_';
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
     * @return string
     */
    protected function getImportFileName($importId, $fileBaseName)
    {
        $filename = str_pad($importId, 4, "0", STR_PAD_LEFT) . '-' . $fileBaseName;
        $falFolder = $this->getFalFolder();
        return $this->getStorage()->sanitizeFileName($filename, $falFolder);
    }

    /**
     * Add the file references for all new and updated files
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     */
    public function runQueue()
    {
        $this->cleanupFiles();
        foreach ($this->newReferenceQueue as $queueEntry) {
            /** @var ImportedModelInterface $object */
            $object = $queueEntry['object'];
            $importId = (int)$queueEntry['import_id'];
            if (!empty($queueEntry['file_exists'])) {
                $existingReference = $this->getFileReferenceIfExists($object, $queueEntry['property'], $queueEntry['data']);
                if (null === $existingReference && (($file = $queueEntry['file']) instanceof File)) {
                    $this->addFileReference($object, $file, $importId, $queueEntry['property']);
                }
            } else {
                $downloadFile = $this->getDownloadFromQueueEntry($queueEntry);
                if (!empty($downloadFile)) {
                    $filename = $queueEntry['target_file_name'];
                    $this->createAndAttachFile($downloadFile, $filename, $importId, $object, $queueEntry['property']);
                }
            }
        }
    }

    /**
     * Returns the list of file reference uid's to be excluded from deletion
     * @return array|int[]
     */
    public function getExcludeFileReferenceUids()
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
    protected function getFileReferenceIfExists($object, $property, $targetFileName)
    {
        if ((int) $object->getUid() > 0) {
            /** @var FileReference $fileReference */
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
     * @param array $queueEntry
     * @return string|null
     */
    abstract protected function getDownloadFromQueueEntry($queueEntry);
}
