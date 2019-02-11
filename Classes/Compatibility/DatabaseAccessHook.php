<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-08-30
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