<?php
defined('TYPO3_MODE') || die('Access denied.');

$tempColumns = array(

    'import_source' => [
        'exclude' => true,
        'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_sysfile.name',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],
    'import_id' => [
        'exclude' => true,
        'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_sysfile.name',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],
    'imported_at' => [
        'exclude' => true,
        'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang_db.xlf:tx_braineventconnector_domain_model_sysfile.name',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim'
        ],
    ],

);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_reference',$tempColumns);
