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

namespace BrainAppeal\CampusEventsConnector\Importer\DBAL;

class DBALFactory
{

    /**
     * @var \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface
     */
    protected static $instance;

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\DBAL\DBAL::class);
        }

        return self::$instance;
    }
}
