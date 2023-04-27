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
     * @var ?ImportObjectGenerator
     */
    private ?ImportObjectGenerator $importObjectGenerator;
    /**
     * @var ApiConnector
     */
    private ApiConnector $apiConnector;

    public function __construct(ApiConnector $apiConnector)
    {
        $this->apiConnector = $apiConnector;
        /** @var ImportObjectGenerator $importObjectGenerator */
        $this->importObjectGenerator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SpecifiedImportObjectGenerator::class);
    }

    /**
     * @param string $baseUri
     * @param string $apiKey
     * @return ApiConnector
     */
    private function getApiConnector(string $baseUri, string $apiKey)
    {
        $apiConnector = $this->apiConnector;
        $apiConnector->setBaseUri($baseUri);
        $apiConnector->setApiKey($apiKey);

        return $apiConnector;
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
        $this->importObjectGenerator->init($baseUri, $pid);
        $dbal = $this->getDBAL();
        $fileImporter = $this->getFileImporter($storageId, $storageFolder);

        $imports = [
            'filter_categories' => FilterCategory::class,
            'events'            => Event::class,
        ];

        foreach ($imports as $alias => $modelClass) {
            $apiResponse = $apiConnector->getApiResponse($alias);
            $objects = $this->importObjectGenerator->generateMultiple($modelClass,$apiResponse['data'][$alias]);
            $dbal->updateObjects($objects);
        }

        foreach ($this->importObjectGenerator->getModifiedObjectClasses() as $modelClass) {
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
        return $this->importObjectGenerator->getDataChanged();
    }
}
