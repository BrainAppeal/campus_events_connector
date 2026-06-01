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

$tableName = 'tx_campuseventsconnector_domain_model_eventattachment';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventattachment',
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
            'default' => 'campus-events-eventattachment',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'name, external_resource_url, attachment_file,event,file_hash,
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
        'importField' => 'EventAttachment',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'event_attachments',
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
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventattachment.name',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'name',
                ],
            ],
            'external_resource_url' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventattachment.external_resource_url',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => ['attachmentFile', 'url'],
                ],
            ],
            'attachment_file' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventattachment.attachment_file',
                'config' => [
                    //## !!! Watch out for fieldName different from columnName
                    'type' => 'file',
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
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
                    'field' => ['attachmentFile', 'url'],
                    'custom_process' => 'files', //Field handled separately
                    'alt_text_source_field' => 'name',
                    'name_import_field' => ['attachmentFile', 'name'],
                    'force_processing' => true,
                    'timestamp_import_field' => ['attachmentFile', 'modifiedAt'],
                    'timestamp_normalizer' => 'datetime_to_tstamp',
                ],
            ],
            'event' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventsession.event',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_event',
                    'maxitems' => 1,
                    'default' => 0,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'event',
                    'foreign_match_field' => 'ce_import_id',
                ],
            ],
            'file_hash' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventattachment.file_hash',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'fileHash',
                ],
            ],
        ]
    ),
];
