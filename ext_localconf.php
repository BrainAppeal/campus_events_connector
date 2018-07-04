<?php
defined('TYPO3_MODE') or die();

// Add caching framework garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][BrainAppeal\BrainEventConnector\Task\EventImportTask::class] = array(
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:tx_braineventconnector_task_eventimporttask.name',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:tx_braineventconnector_task_eventimporttask.description',
        'additionalFields' => \BrainAppeal\BrainEventConnector\Task\EventImportAdditionalFieldProvider::class
);