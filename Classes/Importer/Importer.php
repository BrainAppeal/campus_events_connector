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
 * @since     2018-06-21
 */

namespace BrainAppeal\BrainEventConnector\Importer;

use BrainAppeal\BrainEventConnector\Domain\Model\Event;
use BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory;
use BrainAppeal\BrainEventConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\BrainEventConnector\Importer\DBAL\DBAL as DBALService;
use BrainAppeal\BrainEventConnector\Importer\ObjectGenerator\ImportObjectGenerator;
use BrainAppeal\BrainEventConnector\Importer\ObjectGenerator\SpecifiedImportObjectGenerator;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class Importer
{

    /**
     * @param string $baseUri
     * @param string $apiKey
     * @return ApiConnector
     */
    private function getApiConnector($baseUri, $apiKey)
    {
        /** @var ApiConnector $apiConnector */
        $apiConnector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ApiConnector::class);
        $apiConnector->setBaseUri($baseUri);
        $apiConnector->setApiKey($apiKey);

        return $apiConnector;
    }

    /**
     * @param string $baseUri
     * @param int $pid
     * @return ImportObjectGenerator
     */
    private function getImportObjectGenerator($baseUri, $pid)
    {
        /** @var ImportObjectGenerator $importObjectGenerator */
        $importObjectGenerator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SpecifiedImportObjectGenerator::class);
        $importObjectGenerator->init($baseUri, $pid);

        return $importObjectGenerator;
    }

    /**
     * @return DBALInterface
     */
    private function getDBAL()
    {
        /** @var DBALInterface $dbal */
        $dbal = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(DBALService::class);

        return $dbal;
    }

    /**
     * @param int $storageId
     * @param string $storageFolder
     * @return FileImporter
     */
    private function getFileImporter($storageId, $storageFolder)
    {
        /** @var FileImporter $fileImporter */
        $fileImporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FileImporter::class);
        $fileImporter->initialize($storageId, $storageFolder);

        return $fileImporter;
    }

    /**
     * @param string $baseUri
     * @param string $apiKey
     * @param int|null $pid
     * @param int $storageId
     * @param string $storageFolder
     * @return bool
     */
    public function import($baseUri, $apiKey, $pid, $storageId, $storageFolder)
    {
        $importStartTimestamp = time();

        $apiConnector = $this->getApiConnector($baseUri, $apiKey);
        $importObjectGenerator = $this->getImportObjectGenerator($baseUri, $pid);
        $dbal = $this->getDBAL();
        $fileImporter = $this->getFileImporter($storageId, $storageFolder);

        $imports = [
            'filter_categories' => FilterCategory::class,
            'events'            => Event::class,
        ];

        foreach ($imports as $alias => $modelClass) {
            $apiResponse = $apiConnector->getApiResponse($alias);
            $objects = $importObjectGenerator->generateMultiple($modelClass,$apiResponse['data'][$alias]);
            $dbal->updateObjects($objects);
        }

        foreach ($importObjectGenerator->getModifiedObjectClasses() as $modelClass) {
            $dbal->removeNotUpdatedObjects($modelClass, $baseUri, $pid, $importStartTimestamp);
        }

        $fileImporter->runQueue();

        $dbal->removeNotUpdatedObjects(FileReference::class, $baseUri, $pid, $importStartTimestamp);

        return true;
    }
}