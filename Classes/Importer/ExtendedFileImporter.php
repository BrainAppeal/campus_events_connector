<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class ExtendedFileImporter implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    private $newReferenceQueue;

    /**
     * @var int[]
     */
    private $updateReferenceIds;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder
     */
    private $falFolder;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    private $storage;

    /**
     * @var int
     */
    private $storageId;

    /**
     * @var string
     */
    private $storageFolder;

    /**
     * @var string
     */
    private $baseUri;

    public function __construct()
    {
        $this->newReferenceQueue = [];
        $this->updateReferenceIds = [];

        $this->storageId = 0;
        $this->storageFolder = 'tx_campuseventsconnector/';
    }

    /**
     * @param int $storageId
     * @param string $storageFolder
     * @param string $baseUri
     */
    public function initialize($storageId, $storageFolder, $baseUri)
    {
        $this->storageId = $storageId;
        $this->storageFolder = $storageFolder;
        $this->baseUri = $baseUri;
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
            $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

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
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @return FileReference|null
     */
    private function getFileReferenceIfExists($object, $property, $data)
    {
        if ((int) $object->getUid() > 0) {
            $size = $data['size'];
            /** @var FileReference $fileReference */
            $fileReferenceList = $object->_getProperty($property);
            if (null !== $fileReferenceList) {
                foreach ($fileReferenceList as $fileReference) {
                    if ($size == $fileReference->getOriginalResource()->getSize()) {
                        return $fileReference;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @param string $tempFilenameAndPath
     * @param string $url
     */
    private function addToQueue($object, $property, $data, $tempFilenameAndPath, $url)
    {
        $this->newReferenceQueue[] = [
            'object' => $object,
            'property' => $property,
            'data' => $data,
            'download' => [
                'file' => $tempFilenameAndPath,
                'url' => $url,
            ],
        ];
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     */
    public function enqueueFileMapping($object, $property, $data)
    {
        if (empty($data['url']) || empty($this->baseUri)) {
            return;
        }

        $existingReference = $this->getFileReferenceIfExists($object, $property, $data);

        if (null !== $existingReference) {
            $fileReferenceUid = $existingReference->getOriginalResource()->getUid();
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
        } else {
            $tempFilenameAndPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('tx_campuseventsconnector_');
            $downloadUrl = rtrim($this->baseUri, '/') . '/' . ltrim($data['url'], '/');
            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $downloadUrl);
        }
    }

    /**
     * @param array $queueEntry
     * @return string|null
     */
    private function getDownloadFromQueueEntry($queueEntry)
    {
        $downloadUrl = $queueEntry['download']['url'];
        $downloadFile = $queueEntry['download']['file'];
        $fileContent = GeneralUtility::getUrl($downloadUrl);
        if (false !== $fileContent) {
            file_put_contents($downloadFile, $fileContent);
            return $downloadFile;
        }

        return null;
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

        $attribs = [
            'ce_import_source'=> $object->getCeImportSource(),
            'ce_import_id'=> $importId,
            'ce_imported_at'=> time(),
        ];
        $this->getDBAL()->addSysFileReference($newFile, $object, $objectProperty, $attribs);
    }

    public function runQueue()
    {
        foreach ($this->newReferenceQueue as $queueEntry) {
            $downloadFile = $this->getDownloadFromQueueEntry($queueEntry);
            if (!empty($downloadFile)) {

                /** @var ImportedModelInterface $object */
                $object = $queueEntry['object'];

                $importId = $queueEntry['data']['id'];
                $fileBaseName =  basename($queueEntry['data']['url']);
                $filename = str_pad($importId, 4, "0", STR_PAD_LEFT) . '-' . $fileBaseName;

                $this->createAndAttachFile($downloadFile, $filename, $importId, $object, $queueEntry['property']);
            }
        }
    }

    public function getExcludeFileReferenceUids()
    {
        return array_values($this->updateReferenceIds);
    }

}
