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
 * @since     2018-07-03
 */

namespace BrainAppeal\BrainEventConnector\Importer\DBAL;


interface DBALInterface
{

    public function findByImport($modelClass, $importSource, $importId, $pid);

    public function updateObjects($objects);

    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp);

    public function addSysFileReference($sysFile, $target, $property, $attribs = []);

    public function updateSysFileReference($sysFileReference, $attribs = []);

    public function checkIfPidIsValid($pid);

}