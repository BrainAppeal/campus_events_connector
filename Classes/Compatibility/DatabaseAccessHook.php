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


namespace BrainAppeal\CampusEventsConnector\Compatibility;


class DatabaseAccessHook
{

    /**
     * This hook is called before any write operation by DataHandler
     *
     * @param string $table
     * @param int $id
     * @param array $fileMetadataRecord
     * @param int|NULL $otherHookGrantedAccess
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return int|null
     */
    public function checkRecordUpdateAccess($table, $id, $fileMetadataRecord, $otherHookGrantedAccess, $dataHandler)
    {
        $accessAllowed = $otherHookGrantedAccess;
        if ($table === 'sys_file_reference' || strpos($table, 'tx_campusevents') === 0) {
            $accessAllowed = 1;
        }

        return $accessAllowed;
    }
}
