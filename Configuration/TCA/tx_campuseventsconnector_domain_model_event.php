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

$importColumns = \BrainAppeal\CampusEventsConnector\Utility\TCAUtility::getImportFieldConfiguration();
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event',
        'label' => 'name',
        'label_alt' => 'subtitle',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
        'searchFields' => 'status,canceled,url,name,subtitle,description,short_description,show_in_news,news_text,learning_objective,images,attachments,registration_possible,min_participants,max_participants,participants,speakers,time_ranges,location,categories,organizer,target_groups,view_lists,filter_categories',
        'iconfile' => 'EXT:campus_events_connector/Resources/Public/Icons/tx_campuseventsconnector_domain_model_event.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'name, subtitle, url, 
        disturber_message,
        short_description, 
        description, 
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.meta_data,
            seo_title, seo_description,
            show_in_news, news_text,
            learning_objective,
            event_attendance_mode, event_number,
            order_type,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.event_times,
            --palette--;;eventTimespan,
            event_sessions,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.registration, 
            registration_possible,
            --palette--;;eventParticipants,
            event_ticket_price_variants, 
            direct_registration_url, external_order_email_address, external_order_url, 
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.relations,
            organizer,
            alternative_events, contact_persons, event_attachments, event_images, locations,
            --palette--;;paletteReferents,
            --palette--;;paletteSponsors,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.classification,
             target_groups, categories, filter_categories, view_lists, 
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.other, 
            status, canceled, speakers, time_ranges, hash, modified_at_recursive,
            location,
        --palette--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.palette.media;eventMedia,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access'],
    ],
    'palettes' => [
        'eventMedia' => ['showitem' => 'images, attachments'],
        'eventTimespan' => ['showitem' => 'start_tstamp, end_tstamp'],
        'eventParticipants' => ['showitem' => 'min_participants, max_participants, participants'],
        'paletteReferents' => ['showitem' => 'referents_title,
            --linebreak--,referents'],
        'paletteSponsors' => ['showitem' => 'sponsors_title, 
            --linebreak--,sponsors'],
        'paletteLanguage' => [
            'showitem' => '
                sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,l10n_parent, l10n_diffsource,
            ',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_campuseventsconnector_domain_model_event',
                'foreign_table_where' => 'AND tx_campuseventsconnector_domain_model_event.pid=###CURRENT_PID### AND tx_campuseventsconnector_domain_model_event.sys_language_uid IN (-1,0)',
                'default' => 0
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled'
                    ]
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
            ],
        ],

        'status' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.status',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'canceled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.canceled',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'subtitle' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.subtitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ],
        ],
        'short_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.short_description',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 3,
            ],
        ],
        'show_in_news' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.show_in_news',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'news_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.news_text',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim'
            ]
        ],
        'learning_objective' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.learning_objective',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'enableRichtext' => true,
            ],
            // 'defaultExtras' => 'richtext:rte_transform' // Deprecated
        ],
        'images' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'images',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ]
                        ],
                    ],
                    'maxitems' => 9999
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
        'attachments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.attachments',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'attachments',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference'
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                                'showitem' => '
                            --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                            --palette--;;filePalette'
                            ]
                        ],
                    ],
                    'maxitems' => 9999
                ]
            ),
        ],
        'registration_possible' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.registration_possible',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled'
                    ]
                ],
                'default' => 0,
            ]
        ],
        'min_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.min_participants',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'max_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.max_participants',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.participants',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'speakers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.speakers',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_speaker',
                'MM' => 'tx_campuseventsconnector_event_speaker_mm',
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
        'time_ranges' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.time_ranges',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_timerange',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.location',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_location',
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_category',
                'MM' => 'tx_campuseventsconnector_event_category_mm',
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
        'organizer' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.organizer',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_organizer',
                'MM' => 'tx_campuseventsconnector_event_organizer_mm',
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
        'target_groups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.target_groups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_targetgroup',
                'MM' => 'tx_campuseventsconnector_event_targetgroup_mm',
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
        'filter_categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.filter_categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_filtercategory',
                'MM' => 'tx_campuseventsconnector_event_filtercategory_mm',
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
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.view_lists',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_viewlist',
                'MM' => 'tx_campuseventsconnector_event_viewlist_mm',
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
        'alternative_events' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.alternative_events',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_event',
                'MM' => 'tx_campuseventsconnector_event_alternative_event_mm',
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
        'contact_persons' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.contact_persons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_contactperson',
                'MM' => 'tx_campuseventsconnector_event_contactperson_mm',
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
        'disturber_message' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.disturber_message',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'start_tstamp' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.start_tstamp',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 12,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'end_tstamp' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.end_tstamp',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 12,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'event_sessions' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_sessions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_eventsession',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'event_attachments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_attachments',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_eventattachment',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],
        'event_images' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_images',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_eventimage',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1
                ],
            ],
        ],

        'event_attendance_mode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_attendance_mode',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'event_number' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_number',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'event_ticket_price_variants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_ticket_price_variants',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_eventticketpricevariant',
                'MM' => 'tx_campuseventsconnector_event_eventticketpricevariant_mm',
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
        'external_order_email_address' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_email_address',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'external_order_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'direct_registration_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.direct_registration_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'locations' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.locations',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_location',
                'MM' => 'tx_campuseventsconnector_event_location_mm',
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
        'modified_at_recursive' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.modified_at_recursive',
            'config' => [
                'type' => 'passthrough'
            ],
        ],
        'order_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int'
            ]
        ],
        'referents' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.referents',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_referent',
                'MM' => 'tx_campuseventsconnector_event_referent_mm',
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
        'referents_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.referents_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'seo_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim'
            ]
        ],
        'seo_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'sponsors_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.sponsors_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'sponsors' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.sponsors',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_sponsor',
                'MM' => 'tx_campuseventsconnector_event_sponsor_mm',
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
        'ce_import_source' => $importColumns['ce_import_source'],
        'ce_import_id' => $importColumns['ce_import_id'],
        'ce_imported_at' => $importColumns['ce_imported_at'],
        'hash' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.hash',
            'config' => [
                'type' => 'none',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],

    ],
];
