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

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'CampusEvents Connector',
    'description' => 'Connecting the event software campus events with TYPO3, to import the event data into TYPO3 as data records. Get futher information about Campus Events: www.campus-events.com',
    'category' => 'be',
    'author' => 'Brain Appeal Team',
    'author_company' => 'Brain Appeal GmbH',
    'author_email' => 'info@brain-appeal.com',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '3.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.13-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
