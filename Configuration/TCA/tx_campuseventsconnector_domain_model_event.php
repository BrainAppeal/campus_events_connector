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
use BrainAppeal\EventManagementBundle\Entity\EventArticleInterface;
use BrainAppeal\EventManagementBundle\Entity\TicketCancellationCondition;
use Doctrine\Common\Collections\Collection;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$importColumns = TCAUtility::getImportFieldConfiguration();
$defaultColumnsColumns = TCAUtility::getDefaultFieldConfiguration(TCAUtility::TABLE_EVENTS);
$extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(TCAUtility::EXT_NAME);
$importFieldsReadOnly = $extConf['tca_fields_read_only'] ?? false;
$enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
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
        'searchFields' => 'status,canceled,url,name,subtitle,description,short_description,learning_objective,min_participants,max_participants,available_tickets,categories,organizer,target_groups,view_lists,filter_categories',
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
            learning_objective,
            event_attendance_mode, event_number,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.event_times,
            --palette--;;eventTimespan,
            event_sessions,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.registration,
            order_type,
            --palette--;;eventParticipants,
            event_ticket_price_variants,
            --palette--;;ticketBookingDates,
            direct_registration_url, external_order_email_address, external_order_email_subject, external_order_email_body, external_order_url,
            not_orderable_message,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.relations,
            organizer,
            alternative_events, contact_persons, event_attachments, event_images, locations,
            --palette--;;paletteReferents,
            --palette--;;paletteSponsors,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.classification,
             target_groups, categories, filter_categories, view_lists,
        --div--;LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tabs.other,
            --palette--;;paletteStatus, modified_at_recursive,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;paletteLanguage,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                --palette--;;access'],
    ],
    'palettes' => [
        'eventTimespan' => ['showitem' => 'start_tstamp, end_tstamp'],
        'eventParticipants' => ['showitem' => 'min_participants, max_participants, show_available_tickets, available_tickets'],
        'paletteReferents' => ['showitem' => 'referents_title,
            --linebreak--,referents'],
        'paletteSponsors' => ['showitem' => 'sponsors_title,
            --linebreak--,sponsors'],
        'paletteStatus' => [
            'showitem' => '
                status, canceled, published, completed, archived,
            ',
        ],
        'ticketBookingDates' => [
            'showitem' => 'ticket_cancellation_until,tickets_from,tickets_till,',
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
    ImportTableConfigurationModel::TCA_IMPORT_KEY => [
        'importField' => 'Event',
        'referenceUid' => TCAUtility::IMPORT_ID_FIELD,
        'apiEndpoint' => 'events',
        'apiListItemContainsAllData' => false,
        'dataTransformerClass' => \BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\EventDataTransformer::class,
        'targetImportSourceField' => $enableMultipleImportSources ? 'ce_import_source' : null,
    ],
    'columns' => array_merge($defaultColumnsColumns, [
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.name',
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
        'subtitle' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.subtitle',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'subtitle',
            ],
        ],
        'url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.url',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => ['@urls', 'eventUrl'],
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'description',
                'normalizer' => 'html_for_rte',
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'shortDescription',
            ],
        ],
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'archived',
            ],
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'learningObjective',
                'normalizer' => 'html_for_rte',
            ],
        ],
        'min_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.min_participants',
            'displayCond' => 'FIELD:order_type:IN:0,4',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'minParticipants',
            ],
        ],
        'max_participants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.max_participants',
            'displayCond' => 'FIELD:order_type:IN:0,4',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'maxParticipants',
            ],
        ],
        'show_available_tickets' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.show_available_tickets',
            'displayCond' => 'FIELD:order_type:IN:0,4',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'showAvailableTickets',
            ],
        ],
        'available_tickets' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.available_tickets',
            'displayCond' => 'FIELD:order_type:IN:0,4',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'availableTickets',
            ],
        ],
        'categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.categories',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'categories',
                'foreign_match_field' => 'ce_import_id',
            ],

        ],
        'organizer' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.organizer',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'organizers',
                'foreign_match_field' => 'ce_import_id',
            ],

        ],
        'target_groups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.target_groups',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'targetGroups',
                'foreign_match_field' => 'ce_import_id',
            ],

        ],
        'filter_categories' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.filter_categories',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'filterCategories',
                'foreign_match_field' => 'ce_import_id',
            ],
        ],
        'view_lists' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.view_lists',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'viewLists',
                'foreign_match_field' => 'ce_import_id',
            ],

        ],
        'alternative_events' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'alternativeEvents',
                    'foreign_match_field' => 'ce_import_id',
                ],
            ],

        ],
        'contact_persons' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.contact_persons',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'contactPersons',
                'foreign_match_field' => 'ce_import_id',
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'startDate',
                'normalizer' => 'datetime_to_tstamp',
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'endDate',
                'normalizer' => 'datetime_to_tstamp',
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
                ],
                'readOnly' => $importFieldsReadOnly,
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
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                ],
                'readOnly' => $importFieldsReadOnly,
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
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                ],
                'readOnly' => $importFieldsReadOnly,
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'eventNumber',
            ],
        ],
        'event_ticket_price_variants' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.event_ticket_price_variants',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_campuseventsconnector_domain_model_eventticketpricevariant',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                ],
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
        'external_order_email_address' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_email_address',
            'displayCond' => 'FIELD:order_type:=:3',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'externalOrderEmailAddress',
            ],
        ],
        'external_order_email_subject' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_email_subject',
            'displayCond' => 'FIELD:order_type:=:3',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'externalOrderEmailSubject',
            ],
        ],
        'external_order_email_body' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_email_body',
            'displayCond' => 'FIELD:order_type:=:3',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'externalOrderEmailBody',
            ],
        ],
        'external_order_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.external_order_url',
            'displayCond' => 'FIELD:order_type:=:2',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'externalOrderUrl',
            ],
        ],
        'direct_registration_url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.direct_registration_url',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => ['@urls', 'directRegistrationUrl'],
            ],
        ],
        'locations' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'locations',
                'foreign_match_field' => 'ce_import_id',
            ],

        ],
        'modified_at_recursive' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.modified_at_recursive',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'modifiedAtRecursive',
                'normalizer' => 'datetime_to_tstamp',
            ],
        ],
        'order_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type.detailed.not_orderable',
                        'value' => \BrainAppeal\CampusEventsConnector\Domain\Model\Event::ORDER_TYPE_NOT_ORDERABLE,
                    ],
                    [
                        'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type.detailed.registration',
                        'value' => \BrainAppeal\CampusEventsConnector\Domain\Model\Event::ORDER_TYPE_CAMPUS_EVENTS_REGISTRATION,
                    ],
                    [
                        'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type.detailed.internal',
                        'value' => \BrainAppeal\CampusEventsConnector\Domain\Model\Event::ORDER_TYPE_CAMPUS_EVENTS_ORDER,
                    ],
                    [
                        'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type.detailed.email',
                        'value' => \BrainAppeal\CampusEventsConnector\Domain\Model\Event::ORDER_TYPE_EXTERNAL_EMAIL,
                    ],
                    [
                        'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.order_type.detailed.external_url',
                        'value' => \BrainAppeal\CampusEventsConnector\Domain\Model\Event::ORDER_TYPE_EXTERNAL_URL,
                    ],
                ],
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'orderType',
            ],
        ],
        'not_orderable_message' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.not_orderable_message',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'notOrderableMessage',
            ],
        ],
        'ticket_cancellation_until' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.ticket_cancellation_until',
            'displayCond' => 'FIELD:order_type:IN:0,4',
            'config' => [
                'type' => 'datetime',
                'size' => 12,
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'ticketCancellationUntil',
                'normalizer' => 'datetime_to_tstamp',
            ],
        ],
        'tickets_from' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tickets_from',
            'displayCond' => 'FIELD:order_type:IN:2,3,4',
            'config' => [
                'type' => 'datetime',
                'size' => 12,
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'ticketsFrom',
                'normalizer' => 'datetime_to_tstamp',
            ],
        ],
        'tickets_till' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.tickets_till',
            'displayCond' => 'FIELD:order_type:IN:2,3,4',
            'config' => [
                'type' => 'datetime',
                'size' => 12,
                'default' => 0,
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'ticketsTill',
                'normalizer' => 'datetime_to_tstamp',
            ],
        ],
        'referents' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
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
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'referents',
                'foreign_match_field' => 'ce_import_id',
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'seoRobotsFollow',
            ],
        ],
        'sponsors_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.sponsors_title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => $importFieldsReadOnly,
            ],
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
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
            ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                'field' => 'sponsors',
                'foreign_match_field' => 'ce_import_id',
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
                'eval' => 'unique',
                'default' => '',
                'readOnly' => $importFieldsReadOnly,
            ],
        ],
    ], $importColumns),
];
