<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationProvider;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportOptionsConfigurationException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ReferenceNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Repository\AbstractImportRowRepository;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract class that provides a base for data transformers,
 * offering functionality for processing and formatting import data records.
 */
readonly class ReferenceWriter extends AbstractImportRowRepository
{
    public function __construct(
        protected LoggerInterface                  $logger,
        protected ImportTableConfigurationProvider $importTableConfigurationProvider
    )
    {
    }

    public function updateManyToOneReferences(string $table): void
    {
        $statements = [];
        $importTableConfiguration = $this->importTableConfigurationProvider->getConfiguration($table);

        foreach ($importTableConfiguration->getImportFieldMap() as $mapEntry) {
            $this->addSqlStatementsForMapEntry($importTableConfiguration, $mapEntry, $statements);
        }
        if (!empty($statements)) {
            $this->executeSql($table, $statements);
        }
    }

    protected function addSqlStatementsForMapEntry(ImportTableConfigurationModel $importTableConfiguration, ImportFieldConfigurationModel $mapEntry, array &$statements): void
    {
        if (!$mapEntry->isReference()) {
            return;
        }
        $table = $importTableConfiguration->getTableName();
        $sourceLanguageField = $importTableConfiguration->getLanguageField();
        $targetField = $mapEntry->getTargetField();
        $targetTable = $mapEntry->getReferenceTable();
        $targetTca = $GLOBALS['TCA'][$targetTable] ?? [];
        if (!empty($targetTca)) {
            $languageField = $targetTca['ctrl']['languageField'] ?? null;
            $transOrigPointerField = $targetTca['ctrl']['transOrigPointerField'] ?? null;
            $foreignMatchField = $mapEntry->get('foreign_match_field');
            $rawValueField = $mapEntry->get('raw_value_field');
            if (!empty($rawValueField)) {
                // Set foreign key values. These should already be set in the import workflow, this is just a fallback
                $refSql = sprintf('UPDATE %s s, %s t SET s.%s = t.uid WHERE s.%s = t.%s', $table, $targetTable, $targetField, $rawValueField, $foreignMatchField);
                if ($sourceLanguageField) {
                    $refSql .= sprintf(' AND s.%s = 0', $sourceLanguageField);
                }
                if ($languageField) {
                    $refSql .= sprintf(' AND t.%s = 0', $languageField);
                }
                $statements[] = $refSql;
            }
            $columns = $targetTca['columns'] ?? [];
            foreach ($columns as $relationCountColumn => $columnConfig) {
                $foreignTable = $columnConfig['config']['foreign_table'] ?? null;
                $foreignField = $columnConfig['config']['foreign_field'] ?? null;
                $inverseTypeIsInline = $columnConfig['config']['type'] === 'inline';
                if ($foreignTable === $table && $foreignField === $targetField) {
                    if ($sourceLanguageField && $languageField && $transOrigPointerField && $inverseTypeIsInline) {
                        // Set foreign key of the uid's of translated records to the same language foreign record
                        // The references have been set to the default language foreign record before
                        $statements[] = sprintf('UPDATE %s s, %s t SET s.%s = t.uid WHERE s.%s = t.%s AND t.%s > 0 AND s.%s = t.%s', $table, $targetTable, $targetField, $sourceLanguageField, $languageField, $transOrigPointerField, $targetField, $transOrigPointerField);
                    }
                    $sql = sprintf('UPDATE %s t SET t.%s = (SELECT COUNT(*) FROM %s s WHERE s.deleted = 0 AND s.%s = t.uid', $targetTable, $relationCountColumn, $table, $targetField);
                    if ($sourceLanguageField) {
                        // Either the source language is the default language or the language of the source and target record is the same
                        if ($languageField) {
                            $sql .=  sprintf(' AND (s.%s = 0 OR s.%s = t.%s)', $sourceLanguageField, $sourceLanguageField, $languageField);
                        } else {
                            $sql .= sprintf(' AND s.%s = 0', $sourceLanguageField);
                        }
                    }
                    $sql .= ');';
                    $statements[] = $sql;
                }
            }
        }
    }

    /**
     * Updates many-to-many references based on the provided mapping and configuration.
     *
     * @param ImportTargetRecordMapping $mapping The reference resolver instance containing the mapping and references.
     * @return void
     */
    public function updateManyToManyReferences(ImportTargetRecordMapping $mapping): void
    {
        foreach ($mapping->getManyToManyReferences() as $mmReference) {
            $table = $mmReference->getTable();
            $importTableConfiguration = $this->importTableConfigurationProvider->getConfiguration($table);
            foreach ($mmReference->getManyToManyReferences() as $targetField => $mmReferences) {
                $mapEntry = $importTableConfiguration->getImportConfigurationForField($targetField);
                if ($mapEntry === null) {
                    throw new ImportOptionsConfigurationException('Many-to-many relation configuration missing for field "' . $targetField . '" in table "' . $table . '".');
                }
                $this->updateManyToManyReferencesForTableField($table, $mmReferences, $mapEntry, $mapping);
            }
        }
    }

    /**
     * @param string $table
     * @param array<string|int, array<string|int, ?int>> $mmReferences The referenced records by their local UID and foreign UID.
     * @param ImportFieldConfigurationModel $mapEntry
     * @param ImportTargetRecordMapping $mapping
     */
    protected function updateManyToManyReferencesForTableField(string $table, array $mmReferences, ImportFieldConfigurationModel $mapEntry, ImportTargetRecordMapping $mapping): void
    {
        $targetField = $mapEntry->getTargetField();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $foreignTable = $mapEntry->getReferenceTable();
        $foreignMatchField = $mapEntry->get('foreign_match_field');
        $mmTable = $mapEntry->getManyToManyTable();
        $connection = $connectionPool->getConnectionForTable($mmTable);
        $tableColumnNames = $this->importTableConfigurationProvider->getTableColumnNames($mmTable);
        $localField = 'uid_local';
        $foreignField = 'uid_foreign';
        if (!isset($tableColumnNames[$localField], $tableColumnNames[$foreignField])) {
            $message = 'Many-to-many relation configuration wrong for field "' . $targetField . '" in table "' . $table . '".';
            if (!isset($tableColumnNames[$localField])) {
                $message .= ' Missing column "' . $localField . '" in table "' . $mmTable . '".';
            }
            if (!isset($tableColumnNames[$foreignField])) {
                $message .= ' Missing column "' . $foreignField . '" in table "' . $mmTable . '".';
            }
            throw new ImportOptionsConfigurationException($message);
        }
        $sortingField = isset($tableColumnNames['sorting']) ? 'sorting' : null;
        $foreignSortingField = isset($tableColumnNames['sorting_foreign']) ? 'sorting_foreign' : null;
        // Map existing rows by uid to allow fast lookup of existing records
        $existingRowsByUidLocal = $this->fetchMMRowsForTableByUidLocal($mmTable, $localField, $foreignField);
        foreach ($mmReferences as $uidLocal => $mapUidForeignList) {
            $maxSorting = 0;
            $maxForeignSorting = 0;
            $existingRows = $existingRowsByUidLocal[$uidLocal] ?? [];
            if ($sortingField || $foreignSortingField) {
                foreach ($existingRows as $row) {
                    if ($sortingField && $row[$sortingField] > $maxSorting) {
                        $maxSorting = $row[$sortingField];
                    }
                    if ($foreignSortingField && $row[$foreignSortingField] > $maxForeignSorting) {
                        $maxForeignSorting = $row[$foreignSortingField];
                    }
                }
            }
            $countReferences = 0;
            foreach ($mapUidForeignList as $sourceIdentifier => $uidForeign) {
                if ($uidForeign === null) {
                    try {
                        $uidForeign = $mapping->getTargetReferenceId($foreignTable, $foreignMatchField, $sourceIdentifier, 0);
                    } catch (ReferenceNotFoundException $e) {
                        $this->logger->warning('MM-Relation not found: ' . $e->getMessage(), [
                            'table' => $table,
                            'target_field' => $targetField,
                            'uid_local' => $uidLocal,
                            'sourceIdentifier' => $sourceIdentifier,
                            'foreignMatchField' => $foreignMatchField
                        ]);
                        continue;
                    }
                    if ($uidForeign === null) {
                        continue;
                    }
                }
                ++$countReferences;
                $row = $existingRows[$uidForeign] ?? null;
                if ($row) {
                    unset($existingRows[$uidForeign]);
                } else {
                    $row = [
                        $localField => $uidLocal,
                        $foreignField => $uidForeign,
                    ];
                    if ($sortingField) {
                        ++$maxSorting;
                        $row[$sortingField] = $maxSorting;
                    }
                    if ($foreignSortingField) {
                        ++$maxForeignSorting;
                        $row[$foreignSortingField] = $maxForeignSorting;
                    }
                    $connection->insert($mmTable, $row);
                }
            }
            if (!empty($existingRows)) {
                $queryBuilder = $connectionPool->getQueryBuilderForTable($mmTable);
                $queryBuilder->getRestrictions()->removeAll();
                $queryBuilder->delete($mmTable)
                    ->where(
                        $queryBuilder->expr()->eq($localField, $queryBuilder->createNamedParameter($uidLocal, Connection::PARAM_INT)),
                        $queryBuilder->expr()->in($foreignField, $queryBuilder->createNamedParameter(array_keys($existingRows), Connection::PARAM_INT_ARRAY))
                    )
                    ->executeStatement();
            }
            unset($existingRowsByUidLocal[$uidLocal]);
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->update($table)
                ->set($targetField, $countReferences)
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uidLocal, Connection::PARAM_INT)),
                )->executeStatement();
        }
        if (!empty($existingRowsByUidLocal)) {
            foreach ($mapping->getSkippedRowsByTable($table) as $uid => $skipped) {
                if (isset($existingRowsByUidLocal[$uid])) {
                    unset($existingRowsByUidLocal[$uid]);
                }
            }
        }
        // Delete all rows that are not referenced anymore
        if (!empty($existingRowsByUidLocal)) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($mmTable);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->delete($mmTable)
                ->where(
                    $queryBuilder->expr()->in($localField, $queryBuilder->createNamedParameter(array_keys($existingRowsByUidLocal), Connection::PARAM_INT_ARRAY))
                )
                ->executeStatement();
        }
    }

    /**
     * Fetches rows from a many-to-many (MM) relation table and maps them by the local UID field.
     *
     * @param string $mmTable The name of the MM relation table to fetch rows from.
     * @param string $localField The name of the column representing the local UID field. Defaults to 'uid_local'.
     * @param string $foreignField The name of the column representing the foreign UID field. Defaults to 'uid_foreign'.
     * @return array<int, array<int, array<string, mixed>>> An associative array mapping local UIDs to foreign UIDs and their corresponding rows.
     */
    protected function fetchMMRowsForTableByUidLocal(string $mmTable, string $localField = 'uid_local', string $foreignField = 'uid_foreign'): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($mmTable);
        // Map existing rows by uid to allow fast lookup of existing records
        $existingRowsByUidLocal = [];
        $query = 'SELECT * FROM ' . $mmTable;
        $result = $connection->executeQuery($query);
        while ($row = $result->fetchAssociative()) {
            $uidLocal = (int)$row[$localField];
            $uidForeign = (int)$row[$foreignField];
            $existingRowsByUidLocal[$uidLocal][$uidForeign] = $row;
        }
        $result->free();
        return $existingRowsByUidLocal;
    }

    /**
     * Execute additional SQL statements after import records have been processed
     * @param string $table
     * @param array<string> $statements
     */
    protected function executeSql(string $table, array $statements): void
    {
        if ($statements) {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $connection = $connectionPool->getConnectionForTable($table);
            foreach ($statements as $statement) {
                try {
                    $connection->executeStatement($statement);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage(), ['sql' => $statement]);
                }
            }
        }
    }
}
