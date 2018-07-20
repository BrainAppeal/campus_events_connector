<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_event', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_event.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_event');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_location', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_location.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_location');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_speaker', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_speaker.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_speaker');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_organizer', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_organizer.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_organizer');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_timerange', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_timerange.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_timerange');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_category', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_category.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_category');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_targetgroup', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_targetgroup.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_targetgroup');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_filtercategory', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_filtercategory.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_filtercategory');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_braineventconnector_domain_model_convert2objectconfiguration', 'EXT:brain_event_connector/Resources/Private/Language/locallang_csh_tx_braineventconnector_domain_model_convert2objectconfiguration.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_braineventconnector_domain_model_convert2objectconfiguration');

    }
);
