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

$tableName = 'tx_campuseventsconnector_domain_model_convertconfiguration';
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration($tableName);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'type' => 'type',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            'default' => 'campus-events-convertconfiguration-default'
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'target_pid'
    ],
    'types' => [
        0 => ['showitem' => 'type,
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
        [
            'type' => [
                'exclude' => false,
                'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype_formlabel',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.select_type',
                            'value' => 0,
                            'icon' => 'ext-convertconfiguration-type-default'
                        ]
                    ],
                    'size' => 1,
                    'maxitems' => 1,
                ]
            ],
            'target_pid' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.target_pid',
                'config' => [
                    'type' => 'group',
                    'allowed' => 'pages',
                    'size' => 1,
                    'maxitems' => 1,
                    'minitems' => 0,
                    'default' => 0
                ]
            ],
            'template_path' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.template_path',
                'config' => [
                    'type' => 'folder',
                    'size' => 1,
                    'maxitems' => 1,
                    'minitems' => 0,
                    'eval' => 'trim'
                ],
            ],
            'target_groups' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.target_groups',
                'config' => [
                    'MM' => 'tx_campuseventsconnector_convertconf_targetgroup_mm',
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_targetgroup',
                    'minitems' => 0,
                    'maxitems' => 9999,
                    'size' => 10,
                    'fieldControl' => [
                        'editPopup' => [
                            'disabled' => false
                        ],
                        'addRecord' => [
                            'disabled' => false,
                        ]
                    ],
                ],
            ],
            'filter_categories' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.filter_categories',
                'description' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.filter_categories.description',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_filtercategory',
                    'MM' => 'tx_campuseventsconnector_convertconf_filtercategory_mm',
                    'size' => 10,
                    'autoSizeMax' => 30,
                    'maxitems' => 9999,
                    'multiple' => 0,
                    'fieldControl' => [
                        'editPopup' => [
                            'disabled' => false
                        ],
                        'addRecord' => [
                            'disabled' => false,
                        ]
                    ],
                ],
            ],
            'view_lists' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.view_lists',
                'description' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_convertconfiguration.view_lists.description',
                'config' => [
                    'MM' => 'tx_campuseventsconnector_convertconf_viewlist_mm',
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'foreign_table' => 'tx_campuseventsconnector_domain_model_viewlist',
                    'minitems' => 0,
                    'maxitems' => 9999,
                    'size' => 10,
                    'fieldControl' => [
                        'editPopup' => [
                            'disabled' => false
                        ],
                        'addRecord' => [
                            'disabled' => false,
                        ]
                    ],
                ],
            ],
        ]
    ),
];
