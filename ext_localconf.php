<?php
defined('TYPO3_MODE') or die();

// Add caching framework garbage collection task
/** @var string $_EXTKEY */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BrainAppeal\CampusEventsConnector\Task\EventImportTask::class] = array(
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.name',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.description',
        'additionalFields' => \BrainAppeal\CampusEventsConnector\Task\EventImportAdditionalFieldProvider::class
);

if (TYPO3_MODE === 'BE') {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'ext-convertconfiguration-type-default',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:'.$_EXTKEY.'/Resources/Public/Icons/tx_campuseventsconnector_domain_model_convertconfiguration.svg']
    );
}
