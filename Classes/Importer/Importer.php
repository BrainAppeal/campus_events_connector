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

use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator\ImportObjectGenerator;
use BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator\SpecifiedImportObjectGenerator;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class Importer
{
    /**
     * @var ImportObjectGenerator
     */
    private $importObjectGenerator;

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
        if (null === $this->importObjectGenerator) {
            /** @var ImportObjectGenerator $importObjectGenerator */
            $this->importObjectGenerator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SpecifiedImportObjectGenerator::class);
            $this->importObjectGenerator->init($baseUri, $pid);
        }

        return $this->importObjectGenerator;
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
     * @return bool if any data was changed
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
        $excludeFileReferenceUids = $fileImporter->getExcludeFileReferenceUids();

        $dbal->removeNotUpdatedObjects(FileReference::class, $baseUri, $pid, $importStartTimestamp, $excludeFileReferenceUids);

        return true;
    }

    /**
     * @return bool
     */
    public function hasChangedData()
    {
        if (null !== $this->importObjectGenerator) {
            return $this->importObjectGenerator->getDataChanged();
        }

        return false;
    }
}
