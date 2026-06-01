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

$tableName = 'tx_campuseventsconnector_domain_model_location';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            //            'starttime' => 'starttime',
            //            'endtime' => 'endtime',
        ],
        'searchFields' => 'name,street_name,town,zip_code,building,room,longitude,latitude,list_view_display_name',
        'typeicon_classes' => [
            'default' => 'campus-events-location',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'name, 
        list_view_display_name,
            --palette--;;paletteAddress,
            --palette--;;paletteBuilding,
            --palette--;;paletteGeoCoordinates,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
            --palette--;;access'],
    ],
    'palettes' => [
        'paletteLanguage' => [
            'showitem' => '
                sys_language_uid,l10n_parent, l10n_diffsource,
            ',
        ],
        'access' => [
            'showitem' => 'hidden, starttime, endtime',
        ],
        'paletteGeoCoordinates' => [
            'showitem' => 'longitude,latitude',
        ],
        'paletteBuilding' => [
            'showitem' => 'building,room',
        ],
        'paletteAddress' => [
            'showitem' => 'street_name,--linebreak--,zip_code,town',
        ],
    ],
    ImportTableConfigurationModel::TCA_IMPORT_KEY => [
        'importField' => 'Location',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'locations',
        'apiListItemContainsAllData' => false,
        'dataTransformerClass' => \BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\DefaultDataTransformer::class,
        'targetImportSourceField' => $enableMultipleImportSources ? 'ce_import_source' : null,
    ],
    'columns' => array_merge(
        $defaultColumnsColumns,
        $importColumns,
        [
            'name' => [
                'exclude' => true,
                'l10n_mode' => 'prefixLangTitle',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.name',
                'config' => [
                    'type' => 'input',
                    'eval' => 'trim',
                    'max' => 255,
                    'size' => 50,
                    'required' => true,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'name',
                ],
            ],
            'list_view_display_name' => [
                'exclude' => true,
                'l10n_mode' => 'prefixLangTitle',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.list_view_display_name',
                'config' => [
                    'type' => 'input',
                    'max' => 255,
                    'size' => 50,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'listViewDisplayName',
                ],
            ],
            'street_name' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.street_name',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'streetName',
                ],
            ],
            'town' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.town',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'town',
                ],
            ],
            'zip_code' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.zip_code',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 16,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'zipCode',
                ],
            ],
            'building' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.building',
                'config' => [
                    'type' => 'input',
                    'size' => 25,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'building',
                ],
            ],
            'room' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.room',
                'config' => [
                    'type' => 'input',
                    'size' => 25,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'room',
                ],
            ],
            'longitude' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.longitude',
                'config' => [
                    'type' => 'input',
                    'size' => 15,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'longitude',
                ],
            ],
            'latitude' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_location.latitude',
                'config' => [
                    'type' => 'input',
                    'size' => 15,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'latitude',
                ],
            ],
        ]
    ),
];
