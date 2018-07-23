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

namespace BrainAppeal\BrainEventConnector\Importer;

use BrainAppeal\BrainEventConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\BrainEventConnector\Importer\DBAL\DBALInterface;
use GeorgRinger\News\Domain\Model\FileReference;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;

class FileImporter implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    private $newReferenceQueue;

    /**
     * @var FileReference[]
     */
    private $updateReferenceQueue;

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
        $this->updateReferenceQueue = [];
        $this->client = new Client();

        $this->storageId = 0;
        $this->storageFolder = 'tx_braineventconnector/';
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
        $dbal = \BrainAppeal\BrainEventConnector\Importer\DBAL\DBALFactory::getInstance();

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
     * @param PromiseInterface $promise
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
            $this->updateReferenceQueue[] = $existingReference;
        } else {
            $tempFilenameAndPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('tx_braineventconnector_');

            try {
                $promise = $this->client->requestAsync('get', $data['url'], ['sink' => $tempFilenameAndPath]);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (GuzzleException $e) {
                unset($e);
                return;
            }

            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $promise);
        }
    }

    protected function updateQueuedReferences()
    {
        $dbal = $this->getDBAL();
        $importedAt = time();

        foreach ($this->updateReferenceQueue as $updateReference) {
            $dbal->updateSysFileReference($updateReference, [
                'imported_at'=> $importedAt,
            ]);
        }
    }

    /**
     * @param array $queueEntry
     * @return string|null
     */
    private function getDownloadFromQueueEntry($queueEntry)
    {
        /**
         * @var PromiseInterface $downloadPromise
         */
        $downloadPromise = $queueEntry['download']['promise'];
        $downloadFile = $queueEntry['download']['file'];

        try {
            $downloadPromise->wait();
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (GuzzleException $e) {
            unset($e);
        }
        if (PromiseInterface::FULFILLED == $downloadPromise->getState()) {
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
        $this->updateQueuedReferences();

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

}