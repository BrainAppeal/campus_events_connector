<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Finisher;

use Doctrine\DBAL\Exception;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\RecordDeletedEvent;
use BrainAppeal\CampusEventsConnector\Import\ImportOptionsFactory;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsible for removing obsolete records from the target database tables.
 * It ensures that records not referenced by the current import data are deleted.
 */
class ObsoleteImportedRecordRemover
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected ImportOptionsFactory   $importOptionsFactory,
        protected readonly LoggerInterface $logger,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {}

    protected function getImportOptions(): AbstractImportOptions
    {
        return $this->importOptionsFactory->get();
    }

    /**
     * Deletes obsolete records from the target tables that are no longer referenced
     * by the imported data in the import process.
     * All import tables only contain imported records, so we can hard delete them without checking for manually added records
     *
     * @param int $importId The import id
     * @param int $skippedRowCount The number of skipped rows
     */
    public function run(int $importId, int $skippedRowCount): int
    {
        // If any rows were skipped, this means we didn't fetch all API data, and therefore we don't have all the
        // records in the import table
        // This means we can only delete old records if a full update was done
        if ($skippedRowCount > 0) {
            return 0;
        }
        $deletedRowCount = 0;
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            // Use the data handler first to respect TCA, workspaces, and hooks
            $deletedRowCount += $this->deleteObsoleteRowsForTableWithDataHandler($importId, $dataTransformer->getImportConfiguration());
            // Then delete via SQL to avoid deleting manually added records
            $this->permanentlyDeleteObsoleteRowsForTable($importId, $dataTransformer->getTable());
        }

        return $deletedRowCount;
    }

    /**
     * Deletes obsolete records from the specified table that are no longer linked
     * to the current import process, ensuring old or unlinked entries are cleaned up.
     *
     * @param int $importId The identifier of the current import process.
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel $importConfiguration The table import configuration
     *
     * @return int The number of deleted records.
     */
    private function deleteObsoleteRowsForTableWithDataHandler(int $importId, ImportTableConfigurationModel $importConfiguration): int
    {
        $table = $importConfiguration->getTableName();
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($table);
        // First, determine obsolete record uids via SQL
        $selectSql = sprintf(
            'SELECT p.uid
FROM %s AS p
LEFT JOIN tx_campuseventsconnector_import_row AS ir
  ON p.uid = ir.target_record_uid
 AND ir.import_id = :importId
 AND ir.source_type = :targetTable
WHERE (ir.target_record_uid IS NULL OR ir.target_record_uid = 0)',
            $connection->quoteIdentifier($table),
        );
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        // Respect the record language if available
        if ($languageField) {
            $selectSql .= sprintf(' AND p.%s = ir.sys_language_uid', $languageField);
        }

        $params = [
            'importId' => $importId,
            'targetTable' => $table,
        ];

        $types = [
            'importId' => Connection::PARAM_INT,
            'targetTable' => Connection::PARAM_STR,
        ];
        if (($targetSourceField = $importConfiguration->getTargetImportSourceField()) && $targetSourceValue = $this->getImportOptions()->getTargetImportSource()) {
            $selectSql .= sprintf(' AND p.%s = :targetSourceValue', $connection->quoteIdentifier($targetSourceField));
            $params['targetSourceValue'] = $targetSourceValue;
            $types['targetSourceValue'] = Connection::PARAM_STR;
        }

        try {
            $uids = $connection->executeQuery($selectSql, $params, $types)->fetchFirstColumn();
        } catch (Exception $e) {
            $this->logger->error(sprintf('Selecting obsolete records failed for import entry %d (table %s): %s', $importId, $table, $e->getMessage()));
            $uids = [];
        }

        if (empty($uids)) {
            return 0;
        }
        $start = time();
        foreach ($uids as $uid) {
            // Dispatch event to delete from Solr index
            $this->eventDispatcher->dispatch(
                new RecordDeletedEvent((int)$uid, $table),
            );
            // For some reason deleting with the data handler is super slow on the stage server. We should investigate this.
            /*$cmd = [];
            // Use soft delete via DataHandler (sets deleted flag / handles IRRE)
            $cmd[$table][(int)$uid]['delete'] = 1;

            try {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                // Delete via TYPO3 DataHandler to respect TCA, workspaces, and hooks
                /** @var DataHandler $dataHandler * /
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();
            } catch (\Throwable $t) {
                $this->logger->error(sprintf('DataHandler deletion failed for table %s during import %d: %s', $table, $importId, $t->getMessage()));
            }

            if (!empty($dataHandler->errorLog)) {
                foreach ($dataHandler->errorLog as $error) {
                    $this->logger->error(sprintf('DataHandler error (table %s, import %d): %s', $table, $importId, (string)$error));
                }
            }*/
            // If this takes more than two minutes we skip this part (the data handler is mainly used to clear the indexing queue)
            // Skipped rows will be deleted in permanentlyDeleteObsoleteRowsForTable
            if (time() - $start > 180) {
                break;
            }
        }
        return count($uids);
    }

    /**
     * Permanently deletes obsolete records from the specified table that are no longer linked
     * to the current import process, ensuring old or unlinked entries are removed directly
     * from the database.
     *
     * @param int $importId The identifier of the current import process.
     * @param string $table The name of the database table to clean up obsolete records from.
     *
     * @return void
     */
    private function permanentlyDeleteObsoleteRowsForTable(int $importId, string $table): void
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($table);
        $sql = sprintf(
            'DELETE p
FROM %s AS p
LEFT JOIN tx_campuseventsconnector_import_row AS ir
  ON p.uid = ir.target_record_uid
 AND ir.import_id = :importId
 AND ir.source_type = :sourceType
WHERE (ir.target_record_uid IS NULL OR ir.target_record_uid = 0)',
            $connection->quoteIdentifier($table),
        );
        $sqlTranslations = null;
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        // Don't delete translated records
        if ($languageField) {
            $langCond = sprintf('p.%s = 0', $languageField);
            $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
            if ($transOrigPointerField) {
                $langCond .= ' OR p.' . $transOrigPointerField . ' = 0';
                // Delete translations for records that are no longer linked to the import process
                $sqlTranslations = sprintf(
                    'DELETE FROM %s WHERE sys_language_uid > 0 AND %s > 0 AND %s NOT IN (SELECT uid FROM %s WHERE sys_language_uid = 0)',
                    $connection->quoteIdentifier($table),
                    $connection->quoteIdentifier($transOrigPointerField),
                    $connection->quoteIdentifier($transOrigPointerField),
                    $connection->quoteIdentifier($table)
                );
            }
            $sql .= ' AND (' . $langCond . ')';
        }

        $params = [
            'importId' => $importId,
            'sourceType' => $table,
        ];

        $types = [
            'importId' => Connection::PARAM_INT,
            'sourceType' => Connection::PARAM_STR,
        ];

        try {
            $connection->executeStatement($sql, $params, $types);
            if ($sqlTranslations) {
                $connection->executeStatement($sqlTranslations);
            }
        } catch (Exception $e) {
            $this->logger->error(sprintf('Deleting obsolete records failed for import entry %d: %s', $importId, $e->getMessage()));
        }
    }
}
