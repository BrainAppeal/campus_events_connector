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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Log\Writer\RotatingFileWriter;

defined('TYPO3') or die();


call_user_func(
    static function ($extKey) {
        // Add caching framework garbage collection task
        /** @var string $extKey */

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\BrainAppeal\CampusEventsConnector\Task\EventImportTask::class] = [
            'extension' => $extKey,
            'title' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.name',
            'description' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.description',
            'additionalFields' => \BrainAppeal\CampusEventsConnector\Task\EventImportAdditionalFieldProvider::class
        ];

        // Use a custom log file for this extension
        $logFileInfix = $extKey;
        if ((new Typo3Version())->getMajorVersion() >= 13) {
            $logProcessorConfiguration = [RotatingFileWriter::class => [
                'interval' => Interval::WEEKLY,
                'maxFiles' => 4,
                'logFileInfix' => $logFileInfix
            ]];
        } else {
            $logProcessorConfiguration = [FileWriter::class => [
                'logFileInfix' => $logFileInfix
            ]];
        }
        $minLevel = Environment::getContext()->isDevelopment() ? LogLevel::DEBUG : LogLevel::ERROR;
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['BrainAppeal']['CampusEventsConnector']['writerConfiguration'] = [
            $minLevel => $logProcessorConfiguration
        ];
    },
    'campus_events_connector'
);
