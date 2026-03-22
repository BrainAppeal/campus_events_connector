<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2020 Brain Appeal GmbH
 *
 * @copyright 2020 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Utility;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TCAUtility
{
    public const CACHE_TAG_PREFIX = 'tx_campus_events';
    public const EXT_NAME = 'campus_events_connector';
    public const TABLE_EVENTS = 'tx_campuseventsconnector_domain_model_event';
    public const IMPORT_ID_FIELD = 'ce_import_id';

    public static function getDefaultFieldConfiguration(string $table, bool $addLanguageFields = true): array
    {
        $defaultTcaColumns = [
            'sys_language_uid' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                'config' => [
                    'type' => 'language',
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => '_language_id',
                ],
            ],
            'pid' => [
                'label' => 'pid',
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
            'crdate' => [
                'label' => 'crdate',
                'config' => [
                    'type' => 'datetime',
                ],
            ],
            'tstamp' => [
                'label' => 'tstamp',
                'config' => [
                    'type' => 'datetime',
                ],
            ],
            'hidden' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                ],
            ],
            'starttime' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
                'config' => [
                    'type' => 'datetime',
                ],
            ],
            'endtime' => [
                'exclude' => true,
                'l10n_mode' => 'exclude',
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
                'config' => [
                    'type' => 'datetime',
                ],
            ],
        ];
        if ($addLanguageFields) {
            return array_merge([
                'l10n_parent' => [
                    'displayCond' => 'FIELD:sys_language_uid:>:0',
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
                    'config' => [
                        'type' => 'group',
                        'allowed' => $table,
                        'size' => 1,
                        'maxitems' => 1,
                        'minitems' => 0,
                        'default' => 0,
                    ],
                ],
                'l10n_source' => [
                    'config' => [
                        'type' => 'passthrough',
                    ],
                ],
                'l10n_diffsource' => [
                    'config' => [
                        'type' => 'passthrough',
                    ],
                ],
            ], $defaultTcaColumns);
        }
        return $defaultTcaColumns;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getImportFieldConfiguration(): array
    {
        $tcaColumns = [
            self::IMPORT_ID_FIELD => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.ce_import_id',
                'config' => [
                    'type' => 'number',
                    'size' => 2,
                    'readOnly' => true,
                    'default' => 0
                ],
                ImportTableConfigurationModel::TCA_IMPORT_KEY => [
                    'field' => 'id',
                ],
            ],
            'ce_imported_at' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.ce_imported_at',
                'config' => [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
            ],
            'data_hash' => [
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.data_hash',
                'config' => [
                    'type' => 'passthrough',
                ],
            ],
        ];
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXT_NAME);
        $enableMultipleImportSources = $extConf['enable_multiple_import_sources'] ?? false;
        if ($enableMultipleImportSources) {
            $tcaColumns['ce_import_source'] = [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.name',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => 1,
                ],
            ];
        }
        return $tcaColumns;
    }
}
