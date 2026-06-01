<?php

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Utility\TCAUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$tableName = 'tx_campuseventsconnector_domain_model_timerange';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName, false);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange',
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
            'default' => 'campus-events-timerange',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '--palette--;;paletteTimespan,
        visible_until,
        event, event_session,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
            --palette--;;access',
        ],
    ],
    'palettes' => [
        'paletteTimespan' => ['showitem' => 'start_tstamp, start_date_is_set,
            --linebreak--,
            end_tstamp, end_date_is_set'],
        'paletteLanguage' => [
            'showitem' => '
                sys_language_uid,
            ',
        ],
        'access' => [
            'showitem' => 'hidden, starttime, endtime',
        ],
    ],
    ImportTableConfigurationModel::TCA_IMPORT_KEY => [
        'importField' => 'SessionTimePeriod',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'session_time_periods',
        'apiListItemContainsAllData' => true,
        'dataTransformerClass' => \BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\DefaultDataTransformer::class,
        'targetImportSourceField' => $enableMultipleImportSources ? 'ce_import_source' : null,
    ],
    'columns' => array_merge(
        $defaultColumnsColumns,
        $importColumns,
        [
            'start_tstamp' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.start_tstamp',
                'config' => [
                    'type' => 'datetime',
                    'size' => 12,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'startDate',
                    'normalizer' => 'datetime_to_tstamp',
                ],
            ],
            'start_date_is_set' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.start_date_is_set',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'startDateTimeIsSet',
                ],
            ],
            'end_tstamp' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.end_tstamp',
                'config' => [
                    'type' => 'datetime',
                    'size' => 12,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'endDate',
                    'normalizer' => 'datetime_to_tstamp',
                ],
            ],
            'end_date_is_set' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.end_date_is_set',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'endDateTimeIsSet',
                ],
            ],
            'visible_until' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.visible_until',
                'config' => [
                    'type' => 'datetime',
                    'size' => 12,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'visibleUntil',
                    'normalizer' => 'datetime_to_tstamp',
                ],
            ],
            'event' => [
                'label' => 'event',
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'event_session' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_timerange.event_session',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_eventsession',
                    'maxitems' => 1,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'eventSession',
                    'collection_normalizer' => 'api_reference_to_id',
                ],
            ],
        ]
    ),
];
