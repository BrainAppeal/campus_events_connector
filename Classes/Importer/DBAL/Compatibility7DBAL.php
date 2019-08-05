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


class Compatibility7DBAL extends \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBAL
{
    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        /** @var $connection \TYPO3\CMS\Core\Database\DatabaseConnection */
        $connection = $GLOBALS['TYPO3_DB'];

        return $connection;
    }

    public function checkIfPidIsValid($pid)
    {
        $connection = $this->getDatabaseConnection();

        $select = 'uid';
        $from = 'pages';
        $where = 'uid =' . intval($pid);

        $row = $connection->exec_SELECTgetSingleRow($select, $from, $where);

        return is_array($row) && $row['uid'] == $pid;
    }



    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp, $excludeUids = [])
    {
        if ($modelClass == \TYPO3\CMS\Extbase\Domain\Model\FileReference::class) {
            $tableName = 'sys_file_reference';

            $pid = intval($pid);
            $importSource = preg_replace("/['\"]/", "", $importSource);
            $importTimestamp = intval($importTimestamp);

            $where = sprintf(
                'pid = %d AND ce_import_source = "%s" AND ce_imported_at < %d',
                $pid,
                $importSource,
                $importTimestamp
            );

            $excludeUidsList = implode(',', array_filter($excludeUids,  'is_numeric'));
            if (strlen($excludeUidsList) > 0) {
                $where .= " AND uid NOT IN ($excludeUidsList)";
            }

            $connection = $this->getDatabaseConnection();
            $connection->exec_DELETEquery($tableName, $where);
        } else {
            parent::removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp, $excludeUids);
        }
    }

    private $sysFileReferenceWorkaroundInitialized = false;
    private $backupIgnoreRootLevelRestriction;

    private function enableSysFileReferenceWorkaround()
    {
        if (!$this->sysFileReferenceWorkaroundInitialized) {
            $this->backupIgnoreRootLevelRestriction = $GLOBALS['TCA']['sys_file_reference']['ctrl']['security']['ignoreRootLevelRestriction'];
            $this->sysFileReferenceWorkaroundInitialized = true;
        }

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_campuseventsconnector_compatibility7'] = \BrainAppeal\CampusEventsConnector\Compatibility\DatabaseAccessHook::class;
        $GLOBALS['TCA']['sys_file_reference']['ctrl']['security']['ignoreRootLevelRestriction'] = true;
    }

    private function disableSysFileReferenceWorkaround()
    {
        if ($this->sysFileReferenceWorkaroundInitialized) {
            $GLOBALS['TCA']['sys_file_reference']['ctrl']['security']['ignoreRootLevelRestriction'] = $this->backupIgnoreRootLevelRestriction;
        }
        if (array_key_exists('tx_campuseventsconnector_compatibility7', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'])) {
            unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_campuseventsconnector_compatibility7']);
        }
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $sysFile
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface $target
     * @param string $field
     * @param array $attribs
     */
    public function addSysFileReference($sysFile, $target, $field, $attribs = [])
    {
        $pidBackup = $target->getPid();
        $target->setPid(0);

        $this->enableSysFileReferenceWorkaround();
        parent::addSysFileReference($sysFile, $target, $field, $attribs);
        $this->disableSysFileReferenceWorkaround();

        $target->setPid($pidBackup);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference
     * @param array $attribs
     */
    public function updateSysFileReference($fileReference, $attribs = [])
    {
        $this->enableSysFileReferenceWorkaround();
        parent::updateSysFileReference($fileReference, $attribs);
        $this->disableSysFileReferenceWorkaround();
    }


}
