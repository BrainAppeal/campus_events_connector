<?php

declare(strict_types=1);

use BrainAppeal\CampusEventsConnector\Task\EventImportTask;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    $extKey = 'campus_events_connector';
    // Add custom fields to the tx_scheduler_task table
    // https://docs.typo3.org/c/typo3/cms-scheduler/main/en-us/DevelopersGuide/CreatingTasks/Migration.html#example-migration-scheduler-task-with-additional-fields-suppporting-typo3-13-and-14
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'ce_event_import_base_uri' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.base_uri',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'required' => true,
                    'eval' => 'trim',
                ],
            ],
            'ce_event_import_api_key' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.api_key',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'required' => true,
                    'eval' => 'trim',
                ],
            ],
            'ce_event_import_pid' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.pid',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'pages',
                    'size' => 1,
                    'relationship' => 'manyToOne',
                    'suggestOptions' => [
                        'default' => [
                            'additionalSearchFields' => 'nav_title',
                        ],
                    ],
                    'default' => 0,
                ],
            ],
            /*'ce_event_import_storage_id' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.storage_id',
                'config' => [
                    'type' => 'input',
                    'size' => 10,
                    'required' => true,
                    'eval' => 'int',
                ],
            ],*/
            'ce_event_import_storage_folder' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.storage_folder',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'required' => true,
                    'eval' => 'trim',
                ],
            ],
            'ce_event_import_force_update' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.force_update',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 0,
                ],
            ],
        ]
    );

    // Register the task type
    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.name',
            'description' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.description',
            'value' => EventImportTask::class,
            'icon' => 'campus-events-event',
            'iconOverlay' => 'content-clock',
            'group' => 'ce',
        ],
        '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                ce_event_import_base_uri,
                ce_event_import_api_key,
                ce_event_import_pid,
            file_storage;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageIndexing.storage,
                ce_event_import_storage_folder,
                ce_event_import_force_update,
                description,
            --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,',
        [],
        '',
        'tx_scheduler_task'
    );
}
