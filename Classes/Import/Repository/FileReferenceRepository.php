<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileReferenceModel;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class FileReferenceRepository extends AbstractFileRepository
{
    /**
     * Cleans up file references from the database for the specified tables.
     * This includes deleting file references and the associated files that are no longer valid
     * or referenced by the records in the target tables.
     *
     * @param string[] $tablesWithFileReferences An array of table names that contain file references
     * to be cleaned up. Each table's references will be processed and obsolete records removed.
     */
    public function cleanupFileReferences(array $tablesWithFileReferences): void
    {
        $refTable = 'sys_file_reference';
        $connectionPool = $this->connectionPool;
        $connection = $connectionPool->getConnectionForTable($refTable);
        // Delete all file references and the referenced files for this table
        foreach ($tablesWithFileReferences as $table) {
            $fileRefResult = $this->getFileReferencesForTableRecordList($table, []);
            while ($refRow = $fileRefResult->fetchAssociative()) {
                $fileReferenceModel = new ImportFileReferenceModel($refRow);
                $this->deleteFileAndFileReference($fileReferenceModel);
            }
            $fileRefResult->free();
            $sql = sprintf('DELETE FROM sys_file_reference WHERE tablenames = :table AND uid_foreign NOT IN (SELECT uid FROM %s)', $table);
            $params = [
                'table' => $table,
            ];

            $types = [
                'table' => Connection::PARAM_STR,
            ];

            try {
                $connection->executeStatement($sql, $params, $types);
            } catch (Exception $e) {
                $this->logger->error(sprintf('Deleting obsolete records failed for table %s: %s', $table, $e->getMessage()));
            }
        }
    }

    /**
     * Checks file references within a folder and performs cleanup of orphaned or missing files and their references.
     *
     * @param Folder $folder The folder whose file references should be checked.
     * The folder is validated for sufficient access permissions, and its files and references are processed.
     */
    public function checkFileReferencesInFolder(Folder $folder): void
    {
        try {
            $files = $folder->getStorage()->getFilesInFolder($folder);
        } catch (InsufficientFolderAccessPermissionsException) {
            $this->logger->error(sprintf('The target folder identifier %s does not have sufficient permissions', $folder->getIdentifier()));
            return;
        }
        $refTable = 'sys_file_reference';
        $connectionPool = $this->connectionPool;
        $folderHash = $folder->getHashedIdentifier();
        $queryBuilder = $connectionPool->getQueryBuilderForTable($refTable);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('r.*')
            ->from($refTable, 'r')
            ->leftJoin('r', 'sys_file', 'f', 'f.uid = r.uid_local')
            ->where($queryBuilder->expr()->eq('f.folder_hash', $queryBuilder->createNamedParameter($folderHash, Connection::PARAM_STR)));
        $result = $queryBuilder->executeQuery();
        /** @var array<int, array<string, mixed>> $referencesByFileUid */
        $referencesByFileUid = [];
        while ($row = $result->fetchAssociative()) {
            $fileUid = (int)$row['uid_local'];
            $referencesByFileUid[$fileUid][] = $row;
        }
        foreach ($files as $file) {
            $referencesForFile = $referencesByFileUid[$file->getUid()] ?? [];
            if (empty($referencesForFile)) {
                $this->logger->info(sprintf('Deleting orphaned file %s', $file->getName()));
                $file->delete();
            } elseif ($file->isMissing()) {
                $this->logger->warning(sprintf('File %s is missing and will be deleted', $file->getName()));
                $file->delete();
            } else {
                unset($referencesByFileUid[$file->getUid()]);
            }
        }
        // Delete all remaining references that are not associated with a file anymore
        foreach ($referencesByFileUid as $reference) {
            $this->logger->info(sprintf('Deleting orphaned file reference %s', $reference['uid']));
            $this->deleteFileReference(new ImportFileReferenceModel($reference));
        }
    }

    /**
     * Handles the deletion of a single file reference if its associated local file is missing or removed.
     *
     * @param ImportFileReferenceModel $fileReferenceModel
     *        Mapping info for a file reference (field name, table, target record UID)
     * @return bool
     */
    public function deleteFileAndFileReference(ImportFileReferenceModel $fileReferenceModel): bool
    {
        $this->deleteFileReference($fileReferenceModel);
        return $this->deleteFileByUid($fileReferenceModel->getUidLocal());
    }

    /**
     * Checks the consistency of file references for a given table and target folder identifier.
     *
     * @param string $table The name of the database table to process.
     * @return bool Returns true if all file references are valid; otherwise, false.
     */
    public function checkConsistency(string $table): bool
    {
        $fileRefResult = $this->getFileReferencesForTableRecordList($table, []);
        // The file references will be updated or deleted later, so we don't have to delete them here
        // This is only used as a fallback in case there are inconsistencies
        $deleteMinTstamp = strtotime('-14 days');
        $allReferencesAreValid = true;
        while ($refRow = $fileRefResult->fetchAssociative()) {
            $fileReferenceModel = new ImportFileReferenceModel($refRow);
            $sysFileUid = $fileReferenceModel->getUidLocal();
            $isValid = $sysFileUid > 0;
            if ($isValid) {
                try {
                    $fileObject = $this->resourceFactory->getFileObject($sysFileUid);
                    if ($fileObject->isDeleted() || $fileObject->isMissing()) {
                        $isValid = false;
                    }
                } catch (FileDoesNotExistException) {
                    $isValid = false;
                }
            }
            if (!$isValid) {
                $allReferencesAreValid = false;
                if ($fileReferenceModel->getFileModificationDate() < $deleteMinTstamp) {
                    $this->deleteFileAndFileReference($fileReferenceModel);
                }
            }
        }
        $fileRefResult->free();
        return $allReferencesAreValid;
    }

    /**
     * Executes a file reference query with additional file information (SELECT with JOIN) and returns a Result.
     * @param string $table
     * @param array $recordIdList
     * @param int $languageUid
     * @param string|null $fieldName
     * @return Result
     */
    public function getFileReferencesForTableRecordList(string $table, array $recordIdList, int $languageUid = 0, ?string $fieldName = null): Result
    {
        $refTable = 'sys_file_reference';
        $connectionPool = $this->connectionPool;
        $queryBuilder = $connectionPool->getQueryBuilderForTable($refTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select(
            'r.uid',
            'r.pid',
            'r.tablenames',
            'r.fieldname',
            'r.uid_foreign',
            'r.sorting_foreign',
            'r.uid_local',
            'r.hidden',
            'r.alternative',
            'r.sys_language_uid',
            'r.l10n_parent',
            'f.identifier',
            'f.missing',
            'f.name AS file_name',
            'f.sha1 as file_sha1',
            'f.size as file_size',
            'f.modification_date AS file_modification_date'
        )
            ->from($refTable, 'r')
            ->leftJoin('r', 'sys_file', 'f', 'f.uid = r.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'r.tablenames',
                    $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'r.sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            );
        if (!empty($fieldName)) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'r.fieldname',
                        $queryBuilder->createNamedParameter($fieldName, Connection::PARAM_STR)
                    )
                );
        }
        if (!empty($recordIdList)) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($recordIdList, Connection::PARAM_INT_ARRAY)
                    )
                );
        }
        $queryBuilder->orderBy('r.uid_foreign', 'ASC');
        $queryBuilder->addOrderBy('r.sorting_foreign', 'ASC');
        return $queryBuilder->executeQuery();
    }

    /**
     * Deletes a file reference and safely decrements the corresponding reference counter field of a record.
     *
     * This method removes the given file reference (from sys_file_reference) and updates the specified
     * reference count field in the associated table. The counter is decremented by one but never below zero.
     *
     * @param ImportFileReferenceModel $fileReferenceModel
     */
    public function deleteFileReference(ImportFileReferenceModel $fileReferenceModel): void
    {
        $table = $fileReferenceModel->getTablenames();
        $fieldName = $fileReferenceModel->getFieldname();
        $fileReferenceUid = $fileReferenceModel->getUid();
        $uid = $fileReferenceModel->getUid();
        $refTable = 'sys_file_reference';
        $connectionPool = $this->connectionPool;
        $connection = $connectionPool->getConnectionForTable($refTable);
        // Delete the file reference
        $connection->delete($refTable, [
            'uid' => $fileReferenceUid,
        ]);
        // Decrease the reference count
        /** @noinspection SqlResolve */
        $sql = sprintf(
            'UPDATE %s SET %s = GREATEST(%s - 1, 0) WHERE uid = :uid',
            $table,
            $fieldName,
            $fieldName
        );
        try {
            $connection->executeStatement($sql, ['uid' => $uid]);
        } catch (Exception) {
            $this->logger->error(sprintf('Deleting file reference %d from table %s failed', $fileReferenceUid, $table));
            // Error is ignored because it has no consequences
        }
    }

    /**
     * Saves a file reference to the database using the provided file reference model information.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The model containing the file reference details,
     * such as table names, field names, record ID, PID, and file UID.
     * @return bool Returns true if the file reference was saved without errors, otherwise false.
     */
    public function saveFileReference(ImportFileReferenceModel $fileReferenceModel): bool
    {
        $table = $fileReferenceModel->getTablenames();
        $fieldName = $fileReferenceModel->getFieldname();
        $recordId = $fileReferenceModel->getUidForeign();
        $pid = $fileReferenceModel->getPid();
        $fileUid = $fileReferenceModel->getUidLocal();
        $refTable = 'sys_file_reference';
        if (!$table || !$fieldName || !$recordId || !$pid || !$fileUid) {
            throw new \InvalidArgumentException(sprintf('Invalid file reference model provided: Table: %s.%s; UID: %d; PID: %d; File UID: %d', $table, $fieldName, $recordId, $pid, $fileUid));
        }
        $connectionPool = $this->connectionPool;
        $languageUid = $fileReferenceModel->getLanguageUid();
        $existingFileReferenceUid = $fileReferenceModel->getUid();
        // Assemble DataHandler data
        $referenceId = $existingFileReferenceUid ?: StringUtility::getUniqueId('NEW'); // random string prefixed with NEW
        $data = [];
        $fileReferenceRecord = [
            'uid_local' => $fileUid,
            'tablenames' => $table,
            'uid_foreign' => $recordId,
            'fieldname' => $fieldName,
            'hidden' => 0,
            'pid' => $pid,
            // The alternative text is already saved in the file meta-data
            //'alternative' => $alternative,
        ];
        $tableColumns = $this->getTableColumns($table);
        // If the file reference belongs to a translatable record, we need to localize this record so the translated file reference is created
        if ($languageUid > 0) {
            $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('*')
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($recordId, Connection::PARAM_INT)));
            $row = $queryBuilder->executeQuery()->fetchAssociative();
            if ($row) {
                $l10nParent = null;
                if ($transOrigPointerField) {
                    $rqb = $connectionPool->getQueryBuilderForTable($refTable);
                    $rqb->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $rqb->select('*')
                        ->from($refTable)
                        ->where($rqb->expr()->eq('uid_local', $rqb->createNamedParameter($fileUid, Connection::PARAM_INT)))
                        ->andWhere($rqb->expr()->eq('sys_language_uid', $rqb->createNamedParameter(0, Connection::PARAM_INT)))
                        ->andWhere($rqb->expr()->eq('uid_foreign', $rqb->createNamedParameter((int)$row[$transOrigPointerField], Connection::PARAM_INT)))
                        ->andWhere($rqb->expr()->eq('tablenames', $rqb->createNamedParameter($table, Connection::PARAM_STR)))
                        ->andWhere($rqb->expr()->eq('fieldname', $rqb->createNamedParameter($fieldName, Connection::PARAM_STR)));
                    $refRow = $rqb->executeQuery()->fetchAssociative();
                    if ($refRow) {
                        $l10nParent = $refRow['uid'];
                    } else {
                        return false;
                    }
                }
                $fileReferenceRecord['sys_language_uid'] = $languageUid;
                if ($l10nParent) {
                    $fileReferenceRecord['l10n_parent'] = $l10nParent;
                }
                // Set the alternative text for the translated record
                if ($alternative = $fileReferenceModel->getAlternative()) {
                    $column = $tableColumns['alternative'] ?? null;
                    if ($column && $column->getLength() > 0) {
                        $alternative = $this->ensureMaxBytes($alternative, $column->getLength());
                    }
                    $fileReferenceRecord['alternative'] = $alternative;
                }
                $connection = $connectionPool->getConnectionForTable($refTable);
                if ($existingFileReferenceUid) {
                    $connection->update($refTable, $fileReferenceRecord, ['uid' => $existingFileReferenceUid]);
                } else {
                    $fileReferenceRecord['crdate'] = time();
                    $fileReferenceRecord['tstamp'] = time();
                    $connection->insert($refTable, $fileReferenceRecord);
                    $refCountSql = sprintf('UPDATE %s SET %s = %s + 1 WHERE uid = %d', $table, $fieldName, $fieldName, $recordId);
                    $connection->executeStatement($refCountSql);
                }
            }
            return true;
        }
        $data[$refTable][$referenceId] = $fileReferenceRecord;
        $data[$table][$recordId] = [
            'pid' => $pid,
            $fieldName => $referenceId, // For multiple new references $referenceId is a comma-separated list
        ];
        /** @var DataHandler $dataHandler */
        // Do not inject or reuse the DataHandler as it holds state!
        // Do not use `new` as GeneralUtility::makeInstance handles dependencies
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Process the DataHandler data
        $dataHandler->start($data, []);
        $dataHandler->enableLogging = false;
        $dataHandler->process_datamap();
        if (!$existingFileReferenceUid) {
            $referenceUid = $dataHandler->substNEWwithIDs[$referenceId] ?? null;
            if (!$referenceUid) {
                $this->logger->error(sprintf('News file reference for %s.%s: %d could not created %d by data handler. Will create manually.', $table, $fieldName, $recordId, $fileUid));
                $connection = $connectionPool->getConnectionForTable($refTable);
                $fileReferenceRecord['crdate'] = time();
                $fileReferenceRecord['tstamp'] = time();
                $connection->insert($refTable, $fileReferenceRecord);
                $refCountSql = sprintf('UPDATE %s SET %s = %s + 1 WHERE uid = %d', $table, $fieldName, $fieldName, $recordId);
                $connection->executeStatement($refCountSql);
            }
        }
        if (!empty($dataHandler->errorLog)) {
            $additionalInformation = ', reason "' . implode(', ', $dataHandler->errorLog) . '"';
            $this->logger->error(sprintf('Saving file reference failed for table %s, record %d%s', $table, $recordId, $additionalInformation));
        }
        return $dataHandler->errorLog === [];
    }
}
