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

    public function addSysFileReference($sysFile, $target, $property, $attribs = []);

    public function updateSysFileReference($sysFileReference, $attribs = []);

    public function checkIfPidIsValid($pid);

}
