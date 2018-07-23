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


class Compatibility7DBAL extends \BrainAppeal\BrainEventConnector\Importer\DBAL\DBAL
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



    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp)
    {
        if ($modelClass == \TYPO3\CMS\Extbase\Domain\Model\FileReference::class) {
            $tableName = 'sys_file_reference';

            $pid = intval($pid);
            $importSource = preg_replace("/['\"]/", "", $importSource);
            $importTimestamp = intval($importTimestamp);

            $where = sprintf(
                'pid = %d AND import_source = "%s" AND imported_at < %d',
                $pid,
                $importSource,
                $importTimestamp
            );

            $connection = $this->getDatabaseConnection();
            $connection->exec_DELETEquery($tableName, $where);
        } else {
            parent::removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp);
        }
    }

}