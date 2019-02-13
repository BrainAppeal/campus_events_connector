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

class EventImportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    /**
     * @var string
     */
    public $apiKey;

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
     * @inheritdoc
     */
    public function execute() {
        $importer = $this->getImporter();
        $success = $importer->import($this->baseUri, $this->apiKey, $this->pid, $this->storageId, $this->storageFolder);

        $this->callHooks();

        return $success;
    }

    private function callHooks()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'] as $classRef) {
                $hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'postImport')) {
                    $hookObj->postImport($this->pid);
                }
            }
        }
    }

}
