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

defined('TYPO3') or die();


call_user_func(
    function ($extKey) {
        // Add caching framework garbage collection task
        /** @var string $extKey */

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BrainAppeal\CampusEventsConnector\Task\EventImportTask::class] = [
            'extension' => $extKey,
            'title' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.name',
            'description' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.description',
            'additionalFields' => \BrainAppeal\CampusEventsConnector\Task\EventImportAdditionalFieldProvider::class
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['campusEventsConnector'] = \BrainAppeal\CampusEventsConnector\Updates\ImportFieldNamesUpdateWizard::class;

        // Page TSConfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            chr (10) . '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:campus_events_connector/Configuration/TSConfig/Page/Config.tsconfig">' . chr(10)
        );
    },
    'campus_events_connector'
);
