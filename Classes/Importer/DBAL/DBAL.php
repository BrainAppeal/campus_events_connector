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
use BrainAppeal\CampusEventsConnector\Domain\Model\BelongsToEventInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedApiConnector;
use BrainAppeal\CampusEventsConnector\Importer\ImportMappingModel;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

class DBAL implements DBALInterface, SingletonInterface
{
    /**
     * @var AbstractImportedRepository[]
     */
    private $repositories = [];

    protected ?string $dbImportSource = null;

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
     * @param string $importSource
     * @param int $importId
     * @param ?int $pid
     * @return ?array<string, mixed>
     */
    public function findRowByImport(string $table, string $importSource, int $importId, ?int $pid = null): ?array
    {
        $dbImportSource = $this->getFilteredDbImportSource($importSource);
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('ce_import_source', $queryBuilder->createNamedParameter($dbImportSource, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('ce_import_id', $queryBuilder->createNamedParameter($importId, Connection::PARAM_STR))
            );
        $tableControl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        $languageField = $tableControl['languageField'] ?? '';
        if (!empty($languageField)) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
        }
        if ($pid) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)));
        }
        $queryBuilder->setMaxResults(1);
        $row = $queryBuilder->executeQuery()->fetchAssociative();
        if (empty($row)) {
            return null;
        }
        return $row;
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

    /**
     * @param array<string, array<int, ImportMappingModel>> $groupedImportMappingModels
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function persistImportModels($groupedImportMappingModels): int
    {
        if (empty($groupedImportMappingModels)) {
            return 0;
        }
        $persistedCount = 0;
        // Invalid entities do not need to be persisted, they are removed from the cache
        $externalPersistedCountForCacheUpdate = 0;
        foreach ($groupedImportMappingModels as $importType => $importModelsForType) {
            $objectClass = ExtendedApiConnector::IMPORT_TYPE_CLASS_MAP[$importType];
            $repository = $this->getRepository($objectClass);
            if ($repository instanceof AbstractImportedRepository) {
                foreach ($importModelsForType as $importMappingModel) {
                    /** @var ImportMappingModel $importMappingModel */
                    if (null !== $object = $importMappingModel->getDomainModel()) {
                        /** @var AbstractImportedEntity $object */
                        if ($importMappingModel->isInvalid() || (!$importMappingModel->isDataMapped() && !$object->getUid())) {
                            $repository->remove($object);
                            ++$persistedCount;
                        } elseif (($object instanceof BelongsToEventInterface) && !$object->getEvent()) {
                            $repository->remove($object);
                            $importMappingModel->setIsInvalid(true);
                            if ($object->getUid()) {
                                ++$persistedCount;
                            }
                        } elseif ($object->getUid() > 0) {
                            if ($object->objectIsDirtyDeep()) {
                                $repository->update($object);
                                ++$persistedCount;
                                ++$externalPersistedCountForCacheUpdate;
                            }
                        } else {
                            $repository->add($object);
                            ++$persistedCount;
                            ++$externalPersistedCountForCacheUpdate;
                        }
                    }
                }
            }

        }
        if ($persistedCount > 0) {
            $eventRepository = $this->getRepository(Event::class);
            if ($eventRepository instanceof EventRepository) {
                $eventRepository->persistAll();
            }
        }
        return $externalPersistedCountForCacheUpdate;
    }

    private function deleteRawFromTable(string $tableName, string $importSource, int $pid, int $importTimestamp, array $excludeUids = []): void
    {
        $dbImportSource = $this->fixImportSourceNames($tableName, $importSource);

        /** @noinspection SqlResolve */
        $deleteSql = "DELETE FROM $tableName WHERE pid = ? AND ce_import_source = ? AND ce_imported_at < ?";

        $excludeUidsList = implode(',', array_filter($excludeUids, 'is_numeric'));
        if ($excludeUidsList !== '') {
            $deleteSql .= " AND uid NOT IN ($excludeUidsList)";
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($tableName);
        $connection->executeStatement($deleteSql, [$pid, $dbImportSource, $importTimestamp]);
    }

    /**
     * Extracts and returns the host from the given import source URL. If the host
     * cannot be determined, the original import source is returned.
     *
     * @param string $importSource The import source URL to be filtered.
     * @return string The extracted host from the import source or the original string if no host is present.
     */
    public function getFilteredDbImportSource(string $importSource): string
    {
        if ($this->dbImportSource) {
            return $this->dbImportSource;
        }
        $importSource = preg_replace("/['\"]/", '', $importSource);
        $host = parse_url($importSource, PHP_URL_HOST);
        if ($host) {
            $this->dbImportSource = (string)$host;
        } else {
            $this->dbImportSource = $importSource;
        }
        return $this->dbImportSource;
    }

    public function fixImportSourceNames(string $tableName, string $importSource): string
    {
        $dbImportSource = $this->getFilteredDbImportSource($importSource);
        if ($dbImportSource !== $importSource) {
            $connection = $this->getConnectionForTable($tableName);
            $updateImportSourceSql = "UPDATE $tableName SET ce_import_source = ? WHERE ce_import_source = ?";
            $connection->executeStatement($updateImportSourceSql, [$dbImportSource, $importSource]);
            return $dbImportSource;
        }
        return $importSource;
    }

    /**
     * @inheritDoc
     */
    public function processImportedItems(string $tableName, array $importIdList, string $dbImportSource, int $tstamp): void
    {
        $connection = $this->getConnectionForTable($tableName);
        $uidListCsv = implode(',', array_filter($importIdList, 'is_numeric'));
        $tableControl = $GLOBALS['TCA'][$tableName]['ctrl'] ?? [];
        // Mark all items as deleted that were not included in the api list result
        $markDeletedSql = "UPDATE $tableName SET tstamp = ?, deleted = 1 WHERE ce_import_source = ?";
        if (!empty($uidListCsv)) {
            // Update timestamp for all items from the api list result + mark as not deleted
            $sql = "UPDATE $tableName SET ce_imported_at = ?, deleted = 0 WHERE ce_import_source = ? AND ce_import_id IN ($uidListCsv)";
            $connection->executeStatement($sql, [$tstamp, $dbImportSource]);
            /*
            if (empty($languageField)) {
            } else {
                $sql .= " AND $languageField = 0";
                $connection->executeStatement($sql, [$tstamp, $dbImportSource]);
                $sql = "UPDATE $tableName SET ce_imported_at = ?, deleted = 0 WHERE ce_import_source = ? AND ce_import_id IN ($uidListCsv)";
            }*/
            $markDeletedSql .= " AND ce_import_id NOT IN ($uidListCsv)";
        }
        $connection->executeStatement($markDeletedSql, [$tstamp, $dbImportSource]);
        $languageField = $tableControl['languageField'] ?? '';
        $cleanupSql = "UPDATE $tableName a, $tableName b SET b.deleted = 2 WHERE a.ce_import_source = ? AND a.ce_import_id > 0 AND b.ce_import_source = a.ce_import_source AND a.uid < b.uid AND a.ce_import_id = b.ce_import_id";
        if ($languageField) {
            $sql = 'SELECT DISTINCT ' . $languageField . ' FROM ' . $tableName . ' WHERE ce_import_source = ?';
            $usedLanguages = $connection->executeQuery($sql, [$dbImportSource])->fetchFirstColumn();
            foreach ($usedLanguages as $languageId) {
                $connection->executeStatement($cleanupSql . " AND a.$languageField = $languageId AND b.$languageField = $languageId", [$dbImportSource]);
            }
        } else {
            $connection->executeStatement($cleanupSql, [$dbImportSource]);
        }
        $connection->executeStatement('DELETE FROM ' . $tableName . ' WHERE deleted = 2');
    }

    protected function getConnectionForTable($tableName): Connection
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($tableName);
    }

    public function removeNotUpdatedObjects(string $modelClass, string $dbImportSource, int $pid, int $importTimestamp, array $excludeUids = []): void
    {
        if (is_a($modelClass, FileReference::class, true)) {
            $this->deleteRawFromTable('sys_file_reference', $dbImportSource, $pid, $importTimestamp, $excludeUids);
        } else {
            $repository = $this->getRepository($modelClass);

            if ($repository !== null) {
                $results = $repository->findByNotImportedSince($importTimestamp, $dbImportSource, $pid);
                foreach ($results as $result) {
                    $repository->remove($result);
                }

                $repository->persistAll();
            }
        }
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
        $dataHandler->enableLogging = false;
        $dataHandler->process_datamap();
    }

    /**
     * @param AbstractFile $sysFile
     * @param string $table
     * @param int $storagePid
     * @param int $uidForeign
     * @param string $property
     * @param array $attribs
     * @return int|null
     */
    public function addSysFileReference(AbstractFile $sysFile, string $table, int $storagePid, int $uidForeign, $property, $attribs = []): ?int
    {
        $uidLocal = $sysFile->getUid();

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
        $dataHandler->enableLogging = false;
        $dataHandler->process_datamap();
        if (!empty($dataHandler->substNEWwithIDs[$newId])) {
            return (int)$dataHandler->substNEWwithIDs[$newId];
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
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)
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
        $dataHandler->enableLogging = false;
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
