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

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_event', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_event.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_event');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_location', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_location.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_location');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_speaker', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_speaker.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_speaker');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_organizer', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_organizer.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_organizer');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_timerange', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_timerange.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_timerange');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_category', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_category.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_category');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_targetgroup', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_targetgroup.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_targetgroup');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_filtercategory', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_filtercategory.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_filtercategory');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_campuseventsconnector_domain_model_convertconfiguration', 'EXT:campus_events_connector/Resources/Private/Language/locallang_csh_tx_campuseventsconnector_domain_model_convertconfiguration.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_campuseventsconnector_domain_model_convertconfiguration');

    }
);
