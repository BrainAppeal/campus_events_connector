<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('brain_event_connector', 'Configuration/TypoScript', 'BrainAppeal CampusEvents Connector');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_filtercategory', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_filtercategory.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_filtercategory');

    }
);
