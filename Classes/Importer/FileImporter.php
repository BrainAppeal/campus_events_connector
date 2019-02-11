<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-04
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Http\Promise;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use BrainAppeal\CampusEventsConnector\Http\Client;
use GuzzleHttp\Promise\PromiseInterface;

class FileImporter implements \TYPO3\CMS\Core\SingletonInterface
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
     * @var Client
     */
    private $client;

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

    public function __construct()
    {
        $this->newReferenceQueue = [];
        $this->updateReferenceIds = [];
        $this->client = new Client();

        $this->storageId = 0;
        $this->storageFolder = 'tx_campuseventsconnector/';
    }

    /**
     * @param int $storageId
     * @param string $storageFolder
     */
    public function initialize($storageId, $storageFolder)
    {
        $this->storageId = $storageId;
        $this->storageFolder = $storageFolder;
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
        if (!empty($object->getUid())) {
            $sha1 = $data['hash'];
            /** @var FileReference $fileReference */
            foreach ($object->_getProperty($property) as $fileReference) {
                if ($sha1 == $fileReference->getOriginalResource()->getSha1()) {
                    return $fileReference;
                };
            }
        }

        return null;
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @param string $tempFilenameAndPath
     * @param Promise|PromiseInterface $promise
     */
    private function addToQueue($object, $property, $data, $tempFilenameAndPath, $promise)
    {
        $this->newReferenceQueue[] = [
            'object' => $object,
            'property' => $property,
            'data' => $data,
            'download' => [
                'file' => $tempFilenameAndPath,
                'promise' => $promise,
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
        if (empty($data['hash']) || empty($data['url'])) {
            return;
        }

        $existingReference = $this->getFileReferenceIfExists($object, $property, $data);

        if (null !== $existingReference) {
            $fileReferenceUid = $existingReference->getOriginalResource()->getUid();
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
        } else {
            $tempFilenameAndPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('tx_campuseventsconnector_');

            try {
                $promise = $this->client->getAsync($data['url'], ['sink' => $tempFilenameAndPath]);
            } catch (\BrainAppeal\CampusEventsConnector\Http\HttpException $e) {
                unset($e);
                return;
            }

            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $promise);
        }
    }

    /**
     * @param array $queueEntry
     * @return string|null
     */
    private function getDownloadFromQueueEntry($queueEntry)
    {
        /**
         * @var Promise $downloadPromise
         */
        $downloadPromise = $queueEntry['download']['promise'];
        $downloadFile = $queueEntry['download']['file'];

        try {
            $downloadPromise->wait();
        } catch (\BrainAppeal\CampusEventsConnector\Http\HttpException $e) {
            unset($e);
        }
        if ('fulfilled' == $downloadPromise->getState()) {
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

        $attibs = [
            'import_source'=> $object->getImportSource(),
            'import_id'=> $importId,
            'imported_at'=> time(),
        ];
        $this->getDBAL()->addSysFileReference($newFile, $object, $objectProperty, $attibs);
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
