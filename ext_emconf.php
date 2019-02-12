<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'CampusEvents Connector',
    'description' => '',
    'category' => 'be',
    'author' => 'Joshua Billert',
    'author_company' => 'Brain Appeal GmbH',
    'author_email' => 'info@brain-appeal.com',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'version' => '0.9.0',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-7.6.99|8.7.0-8.7.99|>=9.3.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
