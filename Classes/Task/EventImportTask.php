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

namespace BrainAppeal\CampusEventsConnector\Task;

class EventImportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $apiVersion;

    /**
     * @var string
     */
    public $baseUri;

    /**
     * @var int|null
     */
    public $pid;

    /**
     * @var int
     */
    public $storageId;

    /**
     * @var string
     */
    public $storageFolder;

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\Importer
     */
    private function getImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\Importer $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\Importer::class);

        return $importer;
    }

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter
     */
    private function getExtendedImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter::class);

        return $importer;
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    private function getCacheService()
    {
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->start([], []);

        return $dataHandler;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->apiVersion == 'above-2-27-0') {
            $importer = $this->getExtendedImporter();
        } else {
            $importer = $this->getImporter();
        }

        $success = $importer->import($this->baseUri, $this->apiKey, $this->pid, (int) $this->storageId, $this->storageFolder);

        $this->callHooks();

        if ($importer->hasChangedData()) {
            $this->clearPageCache($this->pid);
        }

        return $success;
    }

    private function clearPageCache($pid)
    {
        $pageIdsToClear[$pid] = $pid;

        $pageTS = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pid);
        if (isset($pageTS['TCEMAIN.']['clearCacheCmd'])) {
            $clearCacheCommands = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($pageTS['TCEMAIN.']['clearCacheCmd']), true);
            $clearCacheCommands = array_unique($clearCacheCommands);
            foreach ($clearCacheCommands as $clearCacheCommand) {
                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($clearCacheCommand)) {
                    $pageIdsToClear[$clearCacheCommand] = $clearCacheCommand;
                }
            }
        }
        foreach ($pageIdsToClear as $ccPid) {
            $this->getCacheService()->clear_cacheCmd($ccPid);
        }
    }

    private function callHooks()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'] as $classRef) {
                $hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'postImport')) {
                    $hookObj->postImport($this->pid);
                }
            }
        }
    }

}
