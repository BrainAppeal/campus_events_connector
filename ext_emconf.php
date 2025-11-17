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

$EM_CONF['campus_events_connector'] = [
    'title' => 'CampusEvents Connector',
    'description' => 'Connecting the event software campus events with TYPO3, to import the event data into TYPO3 as data records. Get further information about Campus Events: www.campus-events.com',
    'category' => 'be',
    'author' => 'Brain Appeal DEV Team',
    'author_company' => 'Brain Appeal GmbH',
    'author_email' => 'info@brain-appeal.com',
    'state' => 'stable',
    'version' => '5.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
