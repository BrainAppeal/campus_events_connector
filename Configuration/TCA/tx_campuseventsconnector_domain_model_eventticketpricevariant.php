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

$tableName = 'tx_campuseventsconnector_domain_model_eventticketpricevariant';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);
$importColumns = TCAUtility::getImportFieldConfiguration();
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
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
//            'disabled' => 'hidden',
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
        '1' => ['showitem' => 'bookable_from,bookable_till,quota,name,price,tax_rate,tax,direct_checkout_url,
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
    'columns' => array_merge(
        $defaultColumnsColumns,
        $importColumns,
        [
            'bookable_from' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.bookable_from',
                'config' => [
                    'dbType' => 'datetime',
                    'type' => 'datetime',
                    'nullable' => true,
                    'size' => 12,
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'bookableFrom',
                    'type' => 'datetime_to_tstamp',
                ],
            ],
            'bookable_till' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.bookable_till',
                'config' => [
                    'dbType' => 'datetime',
                    'type' => 'datetime',
                    'nullable' => true,
                    'size' => 12,
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'bookableTill',
                    'type' => 'datetime_to_tstamp',
                ],
            ],
            'quota' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.quota',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'quota',
                ],
            ],
            'name' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.name',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'name',
                ],
            ],
            'price' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.price',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'price',
                ],
            ],
            'tax_rate' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.tax_rate',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'taxRate',
                ],
            ],
            'tax' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_eventticketpricevariant.tax',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => $importFieldsReadOnly,
                ],
                TCAUtility::TCA_IMPORT_KEY => [
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
                TCAUtility::TCA_IMPORT_KEY => [
                    'field' => 'directCheckoutUrl',
                ],
            ],
        ]
    ),
];
