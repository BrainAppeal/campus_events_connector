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

$importColumns = TCAUtility::getImportFieldConfiguration();
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration(TCAUtility::TABLE_EVENTS);
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event',
        'label' => 'name',
        'label_alt' => 'subtitle',
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
        'searchFields' => 'status,canceled,url,name,subtitle,description,short_description,show_in_news,news_text,learning_objective,images,attachments,registration_possible,min_participants,max_participants,participants,speakers,time_ranges,location,categories,organizer,target_groups,view_lists,filter_categories',
        'typeicon_classes' => [
            'default' => 'campus-events-event',
        ],
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ]
    ],
    'types' => [
        '1' => ['showitem' => 'name, subtitle, url,
        disturber_message,
        short_description,
        description,
        slug,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.meta_data,
            seo_title, seo_description, seo_robots_index, seo_robots_follow,
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
            --palette--;;paletteStatus, speakers, time_ranges, hash, modified_at_recursive,
            location,
        --palette--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.palette.media;eventMedia,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                --palette--;;access'],
    ],
    'palettes' => [
        'eventMedia' => ['showitem' => 'images, attachments'],
        'eventTimespan' => ['showitem' => 'start_tstamp, end_tstamp'],
        'eventParticipants' => ['showitem' => 'min_participants, max_participants, participants'],
        'paletteReferents' => ['showitem' => 'referents_title,
            --linebreak--,referents'],
        'paletteSponsors' => ['showitem' => 'sponsors_title,
            --linebreak--,sponsors'],
        'paletteStatus' => [
            'showitem' => '
                status, canceled, published, completed, archived,
            ',
        ],
        'paletteLanguage' => [
            'showitem' => '
                sys_language_uid,l10n_parent, l10n_diffsource,
            ',
        ],
        'access' => [
            'showitem' => 'hidden, starttime, endtime',
        ],
    ],
    'columns' => array_merge($defaultColumnsColumns, [

        'status' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.status',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
        'canceled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.canceled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'canceled',
            ],
        ],
        'published' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.published',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'published',
            ],
        ],
        'completed' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.completed',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'completed',
            ],
        ],
        'archived' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.archived',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'archived',
            ],
        ],
        'url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'eventUrl',
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.name',
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
        'subtitle' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.subtitle',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'subtitle',
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
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'description',
                'type' => 'html_for_rte',
            ],
        ],
        'short_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.short_description',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 3,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'shortDescription',
            ],
        ],
        'show_in_news' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.show_in_news',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
        'news_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.news_text',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
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
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'learningObjective',
                'type' => 'html_for_rte',
            ],
        ],
        'images' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.images',
            'config' => [
                ### !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
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
                'maxitems' => 9999,
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that file references are copied on localization
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'attachments' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.attachments',
            'config' => [
                ### !!! Watch out for fieldName different from columnName
                'type' => 'file',
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
                'maxitems' => 9999,
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that file references are copied on localization
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'registration_possible' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.registration_possible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ]
        ],
        'min_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.min_participants',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'minParticipants',
            ],
        ],
        'max_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.max_participants',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'maxParticipants',
            ],
        ],
        'participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.participants',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],

        ],
        'disturber_message' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.disturber_message',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'disturberMessage',
            ],
        ],
        'start_tstamp' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.start_tstamp',
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
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.end_tstamp',
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
        'event_sessions' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],

        'event_attendance_mode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_attendance_mode',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'eventAttendanceMode',
            ],
        ],
        'event_number' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_number',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'eventNumber',
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],

        ],
        'external_order_email_address' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_email_address',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'externalOrderEmailAddress',
            ],
        ],
        'external_order_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'externalOrderUrl',
            ],
        ],
        'direct_registration_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.direct_registration_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'directRegistrationUrl',
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],

        ],
        'modified_at_recursive' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.modified_at_recursive',
            'config' => [
                'type' => 'passthrough',
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'modifiedAtRecursive',
                'type' => 'datetime_to_tstamp',
            ],
        ],
        'order_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'orderType',
            ],
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],

        ],
        'referents_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.referents_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'referentsTitle',
            ],
        ],
        'seo_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'seoTitle',
            ],
        ],
        'seo_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'seoDescription',
            ],
        ],
        'seo_robots_index' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_robots_index',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'seoRobotsIndex',
            ],
        ],
        'seo_robots_follow' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.seo_robots_follow',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'seoRobotsFollow',
            ],
        ],
        'sponsors_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.sponsors_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            TCAUtility::TCA_IMPORT_KEY => [
                'field' => 'sponsorsTitle',
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
                'readOnly' => $importFieldsReadOnly,
                // Allow language synchronization so that relations can be localized via DataHandler
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],

        ],

        'slug' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => [
                        'name',
                    ],
                    'fieldSeparator' => '-',
                    'replacements' => [
                        '/' => '-',
                        chr(10) => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
        'hash' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.hash',
            'config' => [
                'type' => 'none',
                'size' => 30,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
    ], $importColumns),
];
