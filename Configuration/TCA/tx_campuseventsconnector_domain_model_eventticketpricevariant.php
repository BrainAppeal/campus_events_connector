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
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$tableName = 'tx_campuseventsconnector_domain_model_eventticketpricevariant';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant',
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
        'searchFields' => 'bookable_from,bookable_till,quota,name,price,tax_rate,tax,direct_checkout_url',
        'typeicon_classes' => [
            'default' => 'campus-events-eventticketpricevariant',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ]
    ],
    'types' => [
        '1' => ['showitem' => 'bookable_from,bookable_till,pv_quota,name,pv_price,pv_tax_rate,tax,direct_checkout_url,price_category,event,
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
        'importField' => 'EventTicketPriceVariant',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'event_ticket_price_variants',
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
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.name',
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
            'bookable_from' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.bookable_from',
                'config' => [
                    'dbType' => 'datetime',
                    'type' => 'datetime',
                    'nullable' => true,
                    'size' => 12,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'bookableFrom',
                    'normalizer' => 'iso_date_to_datetime',
                ],
            ],
            'bookable_till' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.bookable_till',
                'config' => [
                    'dbType' => 'datetime',
                    'type' => 'datetime',
                    'nullable' => true,
                    'size' => 12,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'bookableTill',
                    'normalizer' => 'iso_date_to_datetime',
                ],
            ],
            'pv_quota' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.quota',
                'config' => [
                    'type' => 'number',
                    'size' => 20,
                    'nullable' => true,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'quota',
                ],
            ],
            'pv_price' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.price',
                'config' => [
                    'type' => 'number',
                    'format' => 'decimal',
                    'size' => 20,
                    'nullable' => true,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'price',
                ],
            ],
            'pv_tax_rate' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.tax_rate',
                'config' => [
                    'type' => 'number',
                    'format' => 'decimal',
                    'size' => 20,
                    'nullable' => true,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'taxRate',
                ],
            ],
            'pv_tax' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.tax',
                'config' => [
                    'type' => 'number',
                    'format' => 'decimal',
                    'size' => 20,
                    'nullable' => true,
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'tax',
                ],
            ],
            'direct_checkout_url' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.direct_checkout_url',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => ['@urls', 'directCheckoutUrl'],
                ],
            ],
            'event' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.event',
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
            'price_category' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.price_category',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_pricecategory',
                    'items' => [
                        [
                            'label' => '',
                            'value' => 0,
                        ],
                    ],
                    'minitems' => 0,
                    'maxitems' => 1,
                    'appearance' => [
                        'expandSingle' => true,
                        'showNewRecordLink' => !$importFieldsReadOnly,
                        'enabledControls' => [
                            'info' => !$importFieldsReadOnly,
                            'new' => !$importFieldsReadOnly,
                            'dragdrop' => false,
                            'sort' => false,
                            'hide' => !$importFieldsReadOnly,
                            'delete' => !$importFieldsReadOnly,
                            'localize' => !$importFieldsReadOnly,
                        ],
                    ],
                    //'readOnly' => $importFieldsReadOnly,
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'priceCategory',
                    'foreign_match_field' => 'ce_import_id',
                ],
            ],
        ]
    ),
];
