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
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator\ImportObjectGenerator;
use BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator\SpecifiedImportObjectGenerator;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class Importer
{
    private readonly ImportObjectGenerator $importObjectGenerator;

    public function __construct(private readonly ApiConnector $apiConnector)
    {
        $this->importObjectGenerator = GeneralUtility::makeInstance(SpecifiedImportObjectGenerator::class);
    }

    /**
     * @param string $baseUri
     * @param string $apiKey
     * @return ApiConnector
     */
    private function getApiConnector(string $baseUri, string $apiKey): ApiConnector
    {
        $apiConnector = $this->apiConnector;
        $apiConnector->setBaseUri($baseUri);
        $apiConnector->setApiKey($apiKey);

        return $apiConnector;
    }

    /**
     * @return DBALInterface
     */
    private function getDBAL(): DBALInterface
    {
        $dbal = DBALFactory::getInstance();

        return $dbal;
    }

    /**
     * @param int $storageId
     * @param string $storageFolder
     * @return FileImporter
     */
    private function getFileImporter(int $storageId, string $storageFolder): FileImporter
    {
        /** @var FileImporter $fileImporter */
        $fileImporter = GeneralUtility::makeInstance(FileImporter::class);
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
    public function import(string $baseUri, string $apiKey, ?int $pid, int $storageId, string $storageFolder): bool
    {
        $this->initializeExtbaseEnvironment($pid);
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
        $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($baseUri);
        foreach ($this->importObjectGenerator->getModifiedObjectClasses() as $modelClass) {
            $dbal->removeNotUpdatedObjects($modelClass, $dbImportSource, $pid, $importStartTimestamp);
        }

        $fileImporter->runQueue();
        $excludeFileReferenceUids = $fileImporter->getExcludeFileReferenceUids();

        $dbal->removeNotUpdatedObjects(FileReference::class, $dbImportSource, $pid, $importStartTimestamp, $excludeFileReferenceUids);

        return true;
    }

    /**
     * Since TYPO3 13.4 we need the request object to initialize the configuration manager for Extbase
     * @param int $targetPid
     * @return void
     */
    protected function initializeExtbaseEnvironment(int $targetPid): void
    {
        // TYPO3 >= 13: Initialize TYPO3_REQUEST, so Extbase can be used
        // @see \TYPO3\CMS\Extbase\Configuration\ConfigurationManager::getConfiguration
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
            $pageRecord = BackendUtility::getRecord('pages', $targetPid);
            if ($pageRecord) {
                $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                try {
                    $site = $siteFinder->getSiteByPageId($targetPid);
                    $request = $request->withAttribute('site', $site);
                } catch (SiteNotFoundException $e) {
                    unset($e);
                }
                $request = $request->withQueryParams(['id' => $targetPid]);
            }
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }
    }

    /**
     * @return bool
     */
    public function hasChangedData()
    {
        return $this->importObjectGenerator->getDataChanged();
    }
}
