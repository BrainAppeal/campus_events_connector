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

use BrainAppeal\CampusEventsConnector\Domain\Model\AbstractImportedEntity;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;

class DBAL implements DBALInterface, SingletonInterface
{
    /**
     * @var AbstractImportedRepository[]
     */
    private $repositories = [];

    /**
     * @var string[]
     */
    private $classTableMapping = [];

    /**
     * @param string $modelClass
     * @return AbstractImportedRepository|null
     */
    private function getRepository(string $modelClass): ?AbstractImportedRepository
    {
        if (!isset($this->repositories[$modelClass])) {

            $repository = null;
            $repositoryClass = str_replace('\\Model\\', '\\Repository\\', $modelClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                /** @var AbstractImportedRepository $repository */
                $repository = GeneralUtility::makeInstance($repositoryClass);
            }
            $this->repositories[$modelClass] = $repository;
        }

        return $this->repositories[$modelClass];
    }

    /**
     * @param string $modelClass
     * @param string $importSource
     * @param int $importId
     * @param int|int[]|null $pid
     * @return ImportedModelInterface|null
     */
    public function findByImport(string $modelClass, string $importSource, int $importId, $pid): ?ImportedModelInterface
    {
        $repository = $this->getRepository($modelClass);
        if ($repository === null) {
            return null;
        }

        return $repository->findByImport($importSource, $importId, $pid);
    }

    /**
     * @param ImportedModelInterface[] $objects
     */
    public function updateObjects($objects)
    {
        foreach ($objects as $object) {
            $repository = $this->getRepository($object::class);
            if ($repository instanceof AbstractImportedRepository) {
                /** @var AbstractImportedEntity $object */
                $object->setCeImportedAt(time());
                if ($object->getUid() > 0) {
                    $repository->update($object);
                } else {
                    $repository->add($object);
                }
            }
        }
        if (isset($repository)) {
            $repository->persistAll();
        }
    }

    private function deleteRawFromTable(string $tableName, $importSource, $pid, $importTimestamp, $excludeUids)
    {
        $pid = (int)$pid;
        $importSource = preg_replace("/['\"]/", '', (string)$importSource);
        $importTimestamp = (int)$importTimestamp;

        /** @noinspection SqlResolve */
        $deleteSql = "DELETE FROM $tableName WHERE pid = ? AND ce_import_source = ? AND ce_imported_at < ?";

        $excludeUidsList = implode(',', array_filter($excludeUids, 'is_numeric'));
        if ($excludeUidsList !== '') {
            $deleteSql .= " AND uid NOT IN ($excludeUidsList)";
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($tableName);
        $connection->executeStatement($deleteSql, [$pid, $importSource, $importTimestamp]);
    }

    /**
     * @inheritDoc
     */
    public function processImportedItems($tableName, $importIdList, $importSource, $tstamp)
    {
        $uidListCsv = implode(',', array_filter($importIdList, 'is_numeric'));
        $connection = $this->getConnectionForTable($tableName);
        if (!empty($uidListCsv)) {
            // Update timestamp for all items from the api list result + mark as not deleted
            $sql = "UPDATE $tableName SET ce_imported_at = ?, deleted = 0 WHERE ce_import_source = ? AND ce_import_id IN ($uidListCsv)";
            $connection->executeStatement($sql, [$tstamp, $importSource]);
        }
        // Mark all items as deleted that were not included in the api list result
        $sql = "UPDATE $tableName SET tstamp = ?, deleted = 1 WHERE ce_import_source = ?";
        if (!empty($uidListCsv)) {
            $sql .= " AND ce_import_id NOT IN ($uidListCsv)";
        }
        $connection->executeStatement($sql, [$tstamp, $importSource]);
    }

    protected function getConnectionForTable($tableName)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($tableName);
    }

    public function removeNotUpdatedObjects(string $modelClass, string $importSource, int $pid, int $importTimestamp, array $excludeUids = []): void
    {
        if (is_a($modelClass, FileReference::class, true)) {
            $this->deleteRawFromTable('sys_file_reference', $importSource, $pid, $importTimestamp, $excludeUids);
        } else {
            $repository = $this->getRepository($modelClass);

            if ($repository !== null) {
                $results = $repository->findByNotImportedSince($importTimestamp, $importSource, $pid);
                foreach ($results as $result) {
                    $repository->remove($result);
                }

                $repository->persistAll();
            }
        }
    }

    private function getTableForModelClass($modelClass)
    {
        if (!isset($this->classTableMapping[$modelClass])) {
            $dataMapper = GeneralUtility::makeInstance(DataMapFactory::class);
            $this->classTableMapping[$modelClass] = $dataMapper->buildDataMap($modelClass)->getTableName();
        }

        return $this->classTableMapping[$modelClass];
    }

    /**
     * @param FileReference $sysFileReference
     * @param array $attribs
     */
    public function updateSysFileReference(FileReference $sysFileReference, $attribs = []): void
    {
        $data['sys_file_reference'][$sysFileReference->getUid()] = $attribs;

        // Get an instance of the DataHandler and process the data
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param File $sysFile
     * @param ImportedModelInterface $target
     * @param string $property
     * @param array $attribs
     * @return int|null
     */
    public function addSysFileReference($sysFile, $target, $property, $attribs = [])
    {
        $uidLocal = $sysFile->getUid();
        $uidForeign = $target->getUid();
        $table = $this->getTableForModelClass($target::class);
        $storagePid = $target->getPid();

        $newId = 'NEW' . $uidForeign . '-' . $uidLocal;

        $attribs = array_replace($attribs, [
            'uid_local' => $uidLocal,
            'table_local' => 'sys_file',
            'uid_foreign' => $uidForeign,
            'tablenames' => $table,
            'fieldname' => $property,
            'pid' => $storagePid,
        ]);
        $data = [
            'sys_file_reference' => [$newId => $attribs],
            $table => [$uidForeign => [$property => $newId]],
        ];

        // Get an instance of the DataHandler and process the data
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        if (!empty($dataHandler->substNEWwithIDs[$newId])) {
            return $dataHandler->substNEWwithIDs[$newId];
        }
        return null;
    }

    /**
     * Deletes all file references in the database for a given file.
     *
     * @param File $file The file for which all references should be deleted.
     * @return void
     */
    public function deleteAllFileReferencesForFile(File $file): void
    {
        // First, fetch all sys_file_reference UIDs that reference the given file
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $referenceUids = $queryBuilder
            ->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if (empty($referenceUids)) {
            return;
        }

        // Build a cmdmap for DataHandler to properly delete each reference record
        $cmd = ['sys_file_reference' => []];
        foreach ($referenceUids as $referenceUid) {
            $cmd['sys_file_reference'][(int)$referenceUid] = ['delete' => 1];
        }

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        // No datamap changes, only command map (deletions)
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }

    /**
     * @param int $pid
     * @return bool
     */
    public function checkIfPidIsValid($pid): bool
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->resetRestrictions();
        $pageRowOrNull = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', (int)$pid))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        return !empty($pageRowOrNull) && (int)$pageRowOrNull['uid'] == $pid;
    }

}
