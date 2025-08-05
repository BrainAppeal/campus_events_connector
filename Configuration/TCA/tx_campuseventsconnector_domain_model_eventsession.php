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

use BrainAppeal\CampusEventsConnector\Utility\TCAUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$tableName = 'tx_campuseventsconnector_domain_model_eventsession';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventsession',
        'label' => 'start_tstamp',
        'label_alt' => 'end_tstamp',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'start_tstamp,end_tstamp',
        'typeicon_classes' => [
            'default' => 'campus-events-eventsession',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ]
    ],
    'types' => [
        '1' => ['showitem' => '--palette--;;paletteTimespan,
        session_time_periods,
        event,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
            --palette--;;access'],
    ],
    'palettes' => [
        'paletteTimespan' => ['showitem' => 'start_tstamp, end_tstamp'],
        'paletteLanguage' => [
            'showitem' => '
                sys_language_uid,l10n_parent, l10n_diffsource,
            ',
        ],
        'access' => [
            'showitem' => 'hidden, starttime, endtime',
        ],
    ],
    'columns' => array_merge(
        $defaultColumnsColumns,
        $importColumns,
        [
            'start_tstamp' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventsession.start_tstamp',
                'config' => [
                    'type' => 'datetime',
                    'size' => 12,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'startDate',
                    'type' => 'datetime_to_tstamp',
                ],
            ],
            'end_tstamp' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventsession.end_tstamp',
                'config' => [
                    'type' => 'datetime',
                    'size' => 12,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'endDate',
                    'type' => 'datetime_to_tstamp',
                ],
            ],
            'session_time_periods' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_sessions',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_timerange',
                    'foreign_field' => 'event_session',
                    'maxitems' => 9999,
                    'appearance' => [
                        'collapseAll' => true,
                        'levelLinksPosition' => 'top',
                        'showSynchronizationLink' => 1,
                        'showPossibleLocalizationRecords' => 1,
                        'showAllLocalizationLink' => 1
                    ],
                    'readOnly' => $importFieldsReadOnly,
                ],
            ],
            'event' => [
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
        ]
    ),
];
