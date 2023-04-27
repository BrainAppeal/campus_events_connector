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


use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

interface DBALInterface
{
    /**
     * @param string $modelClass
     * @param string $importSource
     * @param int $importId
     * @param null|int|int[] $pid
     * @return ImportedModelInterface|null
     */
    public function findByImport(string $modelClass, string $importSource, int $importId, $pid): ?ImportedModelInterface;

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

    /**
     * @param \TYPO3\CMS\Core\Resource\File $sysFile
     * @param ImportedModelInterface $target
     * @param string $field
     * @param array $attribs
     * @return int|null
     */
    public function addSysFileReference($sysFile, $target, $property, $attribs = []);

    public function updateSysFileReference(FileReference $sysFileReference, $attribs = []);

    public function checkIfPidIsValid($pid);

}
