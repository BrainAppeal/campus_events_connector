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

$tableName = 'tx_campuseventsconnector_domain_model_sponsor';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor',
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
        'searchFields' => 'name',
        'typeicon_classes' => [
            'default' => 'campus-events-sponsor',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'name, public_label, url, external_resource_url, image_file, image_hash,
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
    ],
    ImportTableConfigurationModel::TCA_IMPORT_KEY => [
        'importField' => 'Sponsor',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'sponsors',
        'apiListItemContainsAllData' => true,
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
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.name',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'name',
                ],
            ],
            'public_label' => [
                'exclude' => true,
                'l10n_mode' => 'prefixLangTitle',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.public_label',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'publicLabel',
                    'is_optional' => true,
                ],
            ],
            'url' => [
                'exclude' => true,
                'l10n_mode' => 'prefixLangTitle',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.url',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'url',
                ],
            ],
            'external_resource_url' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.external_resource_url',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => ['imageFile', 'url'],
                ],
            ],
            'image_file' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.image_file',
                'config' => [
                    //## !!! Watch out for fieldName different from columnName
                    'type' => 'file',
                    'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                            \TYPO3\CMS\Core\Resource\FileType::TEXT->value => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                            \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                            \TYPO3\CMS\Core\Resource\FileType::AUDIO->value => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                            \TYPO3\CMS\Core\Resource\FileType::VIDEO->value => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                            \TYPO3\CMS\Core\Resource\FileType::APPLICATION->value => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette',
                            ],
                        ],
                    ],
                    'maxitems' => 1,
                    // Allow language synchronization so that file references are copied on localization
                    'behaviour' => [
                        'allowLanguageSynchronization' => true,
                    ],
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => ['imageFile', 'url'],
                    'custom_process' => 'files', //Field handled separately
                    'size_field' => ['imageFile', 'size'],
                    'alt_text_source_field' => 'imageAlt',
                    'name_import_field' => ['imageFile', 'name'],
                    'force_processing' => true,
                    'timestamp_import_field' => ['imageFile', 'modifiedAt'],
                    'timestamp_normalizer' => 'datetime_to_tstamp',
                ],
            ],
            'image_hash' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_sponsor.image_hash',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'imageHash',
                    'is_optional' => true,
                ],
            ],
        ]
    ),
];
