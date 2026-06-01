<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use BrainAppeal\CampusEventsConnector\Import\Exception\ImportAlreadyRunningException;
use BrainAppeal\CampusEventsConnector\Import\Finisher\CleanupService;
use BrainAppeal\CampusEventsConnector\Import\Finisher\ObsoleteImportedRecordRemover;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * The ImportEntryManager handles the management of import entries, including
 * starting, stopping, and maintaining the data integrity of import operations.
 */
readonly class ImportEntryManager
{
    public const TABLE_IMPORT = 'tx_campuseventsconnector_import';

    public function __construct(
        protected ImportRecordReader $importRecordReader,
        protected ImportRecordWriter $importRecordWriter,
        protected CleanupService $cleanupService,
        protected ObsoleteImportedRecordRemover $obsoleteImportedRecordRemover,
        protected ConnectionPool $connectionPool,
    ) {}

    public function getImportRecordReader(): ImportRecordReader
    {
        return $this->importRecordReader;
    }

    public function getImportRecordWriter(): ImportRecordWriter
    {
        return $this->importRecordWriter;
    }

    /**
     * Stop all import entries on the given page.
     * This is useful in case an import process has been exited without properly marking the import entry as not running
     *
     * @param int $pid The page ID to scope clean-up operations
     */
    public function stopImportEntriesMarkedAsRunning(int $pid): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_IMPORT);
        $sql = 'UPDATE tx_campuseventsconnector_import SET running = 0 WHERE deleted = 0 AND running = 1 AND pid = :pid';
        $connection->executeStatement($sql, ['pid' => $pid], ['pid' => Connection::PARAM_INT]);
    }

    /**
     * Forces the start of a new import entry by deleting all existing import entries for the given data source.
     *
     * @param string $dataSource Data source name; only import entries for this source are deleted.
     */
    public function forceStartOfNewImportEntry(string $dataSource): void
    {
        $this->cleanupService->deleteImportEntriesForDataSource($dataSource);
    }

    /**
     * Returns the import entry or null if the import is currently running
     *
     * @param string $dataSource
     * @param int $pid
     * @param ?int $dataSourceLastModified Unix timestamp with the last modification of the data source
     * @return ImportEntry
     * @throws ImportAlreadyRunningException
     */
    public function getCurrentImportEntry(string $dataSource, int $pid, ?int $dataSourceLastModified): ImportEntry
    {
        $import = $this->findOpenForDataSourceInPid($dataSource, $pid);
        if ($dataSourceLastModified && $import !== null && $this->stopImportEntryOnSourceUpdate($import, $dataSourceLastModified)) {
            $import = null;
        }
        if ($import === null) {
            $import = new ImportEntry();
            $import->setImportSource($dataSource);
            $import->setPid($pid);
            $this->markImportEntryAsRunning($import, true, $dataSourceLastModified);
        } elseif (!$import->isRunning() || $import->getTstamp() - $import->getCrdate() > 86400) {
            $this->markImportEntryAsRunning($import, false, $dataSourceLastModified);
        } else {
            throw new ImportAlreadyRunningException($import->getUid());
        }
        return $import;
    }

    /**
     * Find first import entry in given PID
     *
     * @param string $dataSource
     * @param int $pid
     * @param bool|int $openState
     * @param int|null $excludeUid
     * @return ImportEntry|null
     */
    private function findOpenForDataSourceInPid(string $dataSource, int $pid = 0, bool|int $openState = true, ?int $excludeUid = null): ?ImportEntry
    {
        $queryBuilder = $this->getQueryBuilderForImportTable();
        $queryBuilder->select('*')
            ->from(self::TABLE_IMPORT)
            ->where($queryBuilder->expr()->eq('pid', $pid))
            ->andWhere($queryBuilder->expr()->eq('import_source', $queryBuilder->createNamedParameter($dataSource)));
        if ($openState === true || $openState === 1) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('import_end', 0));
        } elseif ($openState === -1) {
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->andWhere($queryBuilder->expr()->gt('import_end', 0));
        }
        if ($excludeUid) {
            $queryBuilder->andWhere($queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($excludeUid)));
        }
        $queryBuilder->orderBy('crdate', 'DESC');
        $queryBuilder->setMaxResults(1);
        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (!empty($result)) {
            return new ImportEntry($result);
        }
        return null;
    }

    /**
     * Marks the given import entry as running, setting relevant timestamps and statuses.
     *
     * @param ImportEntry $import The import entry to be marked as running
     * @param bool $isStartOfFullSync Indicates if this is the start of a full synchronization
     * @param ?int $dataSourceLastModified Unix timestamp representing the last modification of the data source, or null if not applicable
     */
    protected function markImportEntryAsRunning(ImportEntry $import, bool $isStartOfFullSync, ?int $dataSourceLastModified): void
    {
        $modifiedAt = $import->getImportSourceModifiedAt();
        // Restart import if a new record was created
        // or last modified date of import record is empty or less than the data source last-modified value
        if ($isStartOfFullSync || $modifiedAt === null
            || ($dataSourceLastModified && $modifiedAt->getTimestamp() < $dataSourceLastModified)) {
            $modifiedAt = date_create();
            if ($dataSourceLastModified) {
                $modifiedAt->setTimestamp($dataSourceLastModified);
            }
            $import->setImportSourceModifiedAt($modifiedAt);
            $importStart = date_create();
            $import->setImportStart($importStart);
            $import->setImportedRowCount(0);
            $import->setFullLoadedRowCount(0);
            $import->setTotalRowCount(0);
            $import->setFirstImportDone(false);
        } else {
            // Existing record is updated
            $import->setFirstImportDone(true);
        }
        $import->setRunning(true);
        if (!$import->getUid()) {
            $queryBuilder = $this->getQueryBuilderForImportTable();
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->insert(self::TABLE_IMPORT)
                ->values($import->toArray())
                ->executeStatement();
            $tableUid = $queryBuilder->getConnection()->lastInsertId();
            $import->setUid((int)$tableUid);

        } else {
            $this->update($import);
        }
    }

    /**
     * Stop the current import process if the data source has been updated
     *
     * @param ImportEntry $importEntry
     * @param int $dataSourceLastModified
     * @return bool Return true if an old import entry was stopped
     */
    protected function stopImportEntryOnSourceUpdate(ImportEntry $importEntry, int $dataSourceLastModified): bool
    {
        if ($importEntry->getImportEnd() === null) {
            $modifiedAt = $importEntry->getImportSourceModifiedAt();
            if ($modifiedAt === null || $modifiedAt->getTimestamp() < $dataSourceLastModified) {
                $this->cleanupService->deleteImportRows([$importEntry->getUid()]);
                $importEntry->markAsFinished();
                $this->update($importEntry);
                return true;
            }
        }
        return false;
    }

    /**
     * Refreshes the row count data for a specific import entry. This includes updating the total number
     * of rows, the number of fully loaded rows, the number of imported rows, and the number of skipped rows
     * for the given import ID.
     *
     * @param ImportEntry $importEntry
     * @return array{total_rows: int, full_loaded_rows: int, imported_rows: int, skipped_rows: int}|false
     */
    public function updateActiveEntry(ImportEntry $importEntry): array|false
    {
        $statsRow = $this->refreshImportCounts($importEntry);
        if ($importEntry->isImportFinished()) {
            $importEntry->markAsFinished();
        }
        if ($statsRow) {
            $this->update($importEntry);
        }
        return $statsRow;
    }

    /**
     * @param ImportEntry $importEntry
     * @return array{total_rows: int, full_loaded_rows: int, imported_rows: int, skipped_rows: int}|false
     */
    protected function refreshImportCounts(ImportEntry $importEntry): array|false
    {
        $statsRow = $this->importRecordReader->getRowCountsForImport($importEntry->getUid());
        if (!empty($statsRow)) {
            $importEntry->setTotalRowCount((int)$statsRow['total_rows']);
            $importEntry->setFullLoadedRowCount((int)$statsRow['full_loaded_rows']);
            $importEntry->setSkippedRowCount((int)$statsRow['skipped_rows']);
            $importEntry->setImportedRowCount((int)$statsRow['imported_rows']);
        }
        return $statsRow;
    }

    protected function getQueryBuilderForImportTable(): QueryBuilder
    {
        $table = self::TABLE_IMPORT;
        return $this->connectionPool->getQueryBuilderForTable($table);
    }

    /**
     * Updates the provided import entry in the database.
     *
     * @param ImportEntry $importEntry The import entry to be updated, containing the data mapped to database fields.
     */
    protected function update(ImportEntry $importEntry): void
    {
        $importId = $importEntry->getUid();
        $table = self::TABLE_IMPORT;
        $queryBuilder = $this->getQueryBuilderForImportTable();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update($table);
        $queryBuilder->where(
            $queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
            )
        );
        $array = $importEntry->toArray();
        foreach ($array as $key => $value) {
            if ($key !== 'uid' && $key !== 'tstamp') {
                $queryBuilder->set($key, $value);
            }
        }
        $queryBuilder->set('tstamp', time());
        $queryBuilder->executeStatement();
    }

}
