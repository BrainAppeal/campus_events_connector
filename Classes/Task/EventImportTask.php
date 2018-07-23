<?php
namespace BrainAppeal\BrainEventConnector\Task;

/***
 *
 * This file is part of the "BrainAppeal CampusEvents Connector" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Joshua Billert <joshua.billert@brain-appeal.com>, Brain Appeal GmbH
 *
 ***/

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
     * @return \BrainAppeal\BrainEventConnector\Importer\Importer
     */
    private function getImporter()
    {
        /** @var \BrainAppeal\BrainEventConnector\Importer\Importer $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\BrainEventConnector\Importer\Importer::class);

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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_braineventconnector']['postImport'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_braineventconnector']['postImport'] as $classRef) {
                $hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'postImport')) {
                    $hookObj->postImport($this->pid);
                }
            }
        }
    }

}
