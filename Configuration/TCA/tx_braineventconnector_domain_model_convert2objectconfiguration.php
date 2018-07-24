<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'type' => 'type',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            'default' => 'ext-convert2objectconfiguration-type-default'
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'target_pid',
        'iconfile' => 'EXT:brain_event_connector/Resources/Public/Icons/tx_braineventconnector_domain_model_convert2objectconfiguration.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, type, target_pid, template_path, target_groups, filter_categories',
    ],
    'types' => [
        0 => ['showitem' => 'hidden, type, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ]
                ],
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_braineventconnector_domain_model_convert2objectconfiguration',
                'foreign_table_where' => 'AND tx_braineventconnector_domain_model_convert2objectconfiguration.pid=###CURRENT_PID### AND tx_braineventconnector_domain_model_convert2objectconfiguration.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
            ],
        ],
        'type' => [
            'exclude' => false,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype_formlabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    0 => ['LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration.select_type', 0, 'ext-convert2objectconfiguration-type-default']
                ],
                'showIconTable' => false,
                'size' => 1,
                'maxitems' => 1,
            ]
        ],

        'target_pid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration.target_pid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0
            ]

        ],
        'template_path' => [
            'exclude' => true,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration.template_path',
            'config' => [
                'type' => 'group',
                'internal_type' => 'folder',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'show_thumbs' => 0,
                'eval' => 'trim'
            ],

        ],
        'target_groups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration.target_groups',
            'config' => [
                'MM' => 'tx_braineventconnector_convert2objconf_targetgroup_mm',

                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_braineventconnector_domain_model_filtercategory',
                'minitems' => 0,
                'maxitems' => 9999,

                'size' => 10,
                'treeConfig' => array(
                    'parentField' => 'parent',
                    'appearance' => array(
                        'expandAll' => true,
                        'showHeader' => false,
                    ),
                ),
            ],

        ],
        'filter_categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_convert2objectconfiguration.filter_categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_braineventconnector_domain_model_filtercategory',
                'MM' => 'tx_braineventconnector_convert2objconf_filtercategory_mm',
                'size' => 10,
                'autoSizeMax' => 30,
                'maxitems' => 9999,
                'multiple' => 0,
                'wizards' => [
                    '_PADDING' => 1,
                    '_VERTICAL' => 1,
                    'edit' => [
                        'module' => [
                            'name' => 'wizard_edit',
                        ],
                        'type' => 'popup',
                        'title' => 'Edit', // todo define label: LLL:EXT:.../Resources/Private/Language/locallang_tca.xlf:wizard.edit
                        'icon' => 'actions-open',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                    'add' => [
                        'module' => [
                            'name' => 'wizard_add',
                        ],
                        'type' => 'script',
                        'title' => 'Create new', // todo define label: LLL:EXT:.../Resources/Private/Language/locallang_tca.xlf:wizard.add
                        'icon' => 'actions-add',
                        'params' => [
                            'table' => 'tx_braineventconnector_domain_model_filtercategory',
                            'pid' => '###CURRENT_PID###',
                            'setValue' => 'prepend'
                        ],
                    ],
                ],
            ],
        ],
    
    ],
];
