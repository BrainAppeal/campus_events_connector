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


interface DBALInterface
{

    public function findByImport($modelClass, $importSource, $importId, $pid);

    public function updateObjects($objects);

    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp, $excludeUids = []);

    /**
     * Update the import fields of all records that were found in the api list call;
     * Mark all records as deleted that were not found
     *
     * @param string $tableName
     * @param array $importIdList
     * @param string $importSource
     * @param int $tstamp
     * @return mixed
     */
    public function processImportedItems($tableName, $importIdList, $importSource, $tstamp);

    public function addSysFileReference($sysFile, $target, $property, $attribs = []);

    public function updateSysFileReference($sysFileReference, $attribs = []);

    public function checkIfPidIsValid($pid);

}
