<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\TargetResolution;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Exception\RecordInvalidException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ReferenceNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Repository\AbstractImportRowRepository;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract class that provides a base for data transformers,
 * offering functionality for processing and formatting import data records.
 */
readonly class ReferenceResolver extends AbstractImportRowRepository
{
    private ImportTargetRecordMapping $mapping;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->mapping = new ImportTargetRecordMapping();
    }

    public function getMapping(): ImportTargetRecordMapping
    {
        return $this->mapping;
    }

    /**
     * Adds a mapping for the specified model to the import target record mapping.
     *
     * This method processes the provided import configuration and record model to populate the mapping
     * with identifier references, processed flags, and many-to-many relationship references if applicable.
     * It ensures that the target record, identified by the given model, is correctly mapped in the import process.
     *
     * @param ImportTableConfigurationModel $importConfiguration The configuration for the import table,
     * containing details like the target table name and unique identifier field.
     * @param ImportRecordModel $model The record model containing information about the source record, target record ID,
     * language UID, and any many-to-many relationships.
     * @return void
     */
    public function addMappingForModel(ImportTableConfigurationModel $importConfiguration, ImportRecordModel $model): void
    {
        $uid = $model->getTargetRecordId();
        if (!$uid) {
            return;
        }
        $targetTable = $importConfiguration->getTableName();
        $uniqueTargetIdentifierField = $importConfiguration->getSourceIdentifierField();
        $languageId = $model->getLanguageUid();
        $sourceRecordId = $model->getSourceRecordIdentifier();
        $this->mapping->addProcessed($targetTable, $uid, true, true);
        $this->mapping->addIdentifierReference($targetTable, $uniqueTargetIdentifierField, $sourceRecordId, $uid, $languageId);
        if (!empty($mmReferences = $model->getManyToManyReferences())) {
            $this->mapping->addManyToManyReference($targetTable, $uid, $mmReferences);
        }
    }

    /**
     * Updates the mapping of source entries to target record IDs for a specific import process.
     *
     * This method refreshes the internal mapping that links source entry identifiers and language
     * combinations to their corresponding target record IDs. It queries the import process data to
     * identify existing target record IDs and stores them for further processing.
     *
     * @param int $importId The unique identifier of the import process.
     * @param ImportTableConfigurationModel $importConfiguration
     * @return void
     * @throws Exception
     */
    public function refreshTargetRecordIdMapping(int $importId, ImportTableConfigurationModel $importConfiguration): void
    {
        $targetTable = $importConfiguration->getTableName();
        $sourceIdField = $importConfiguration->getSourceIdentifierField();
        // Load the updated target record ID for existing records
        $includeSkipped = true;
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId, $includeSkipped);
        $queryBuilder->select(ImportRecordModel::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING, 'sys_language_uid', 'target_record_uid', 'import_skipped')
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'source_type',
                    $queryBuilder->createNamedParameter($targetTable)
                ),
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->isNotNull('target_record_uid'),
                    $queryBuilder->expr()->neq(
                        'target_record_uid',
                        $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                    ),
                )
            );
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $sourceRecordId = (string)$row[ImportRecordModel::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING];
            $targetRecordId = (int)$row['target_record_uid'];
            $languageId = (int)$row['sys_language_uid'];
            $this->mapping->addIdentifierReference($targetTable, $sourceIdField, $sourceRecordId, $targetRecordId, $languageId);
            if ($row['import_skipped']) {
                $this->mapping->addSkippedUidForTable($targetTable, $targetRecordId);
            }
        }
        $result->free();
    }

    /**
     * Resolves a raw value to a corresponding target value based on mapping rules.
     * Handles unresolved references by either marking them or assigning a default value.
     *
     * @param ImportRecordModel $model The import record model containing the data.
     * @param mixed $rawValue The raw value to resolve.
     * @param ImportFieldConfigurationModel $mapEntry Mapping information including reference and target details.
     *
     * @return int|null The resolved value, or null if the value is nullable and unresolved.
     * @throws RecordInvalidException
     */
    public function resolve(ImportRecordModel $model, mixed $rawValue, ImportFieldConfigurationModel $mapEntry): ?int
    {
        if (!$rawValue) {
            // Add empty many-to-many references to the model so that they can be processed later if a target record ID exists.
            // This is necessary because for many-to-many relations so we can resolve deleted references for updated records.
            if ($mapEntry->isManyToManyRelation() && $model->getTargetRecordId()) {
                $model->addManyToManyReferences($mapEntry->getTargetField(), []);
            }
            return null;
        }
        try {
            $rawValue = $this->resolveReference($model, $rawValue, $mapEntry);
        } catch (ReferenceNotFoundException $e) {
            $this->logger->warning('Reference not found: ' . $e->getMessage());
            $rawValue = $mapEntry->isNullable() ? null : 0;
        }
        return $rawValue;
    }

    /**
     * Resolves a reference ID based on the provided raw value, mapping entry, and target record mapping.
     *
     * @param ImportRecordModel $model
     * @param mixed $rawValue The raw value to be resolved, which can be a scalar or an array.
     * @param ImportFieldConfigurationModel $mapEntry An associative array defining the reference table and foreign match field.
     * @return int|null The resolved reference ID as an integer if found; null if no matching record exists.
     *
     * @throws ReferenceNotFoundException If the foreign match field is not defined in the mapping entry.
     * @throws RecordInvalidException
     */
    protected function resolveReference(ImportRecordModel $model, mixed $rawValue, ImportFieldConfigurationModel $mapEntry): ?int
    {
        $targetTable = $mapEntry->getReferenceTable();
        $foreignMatchField = $mapEntry->get('foreign_match_field');
        if (empty($foreignMatchField)) {
            throw new ReferenceNotFoundException(sprintf('Can\'t resolve reference for table field "%s.%s": No foreign match field defined.', $model->getTargetTable(), $mapEntry->getTargetField()), 1751549547);
        }
        if (is_array($rawValue)) {
            // Many-to-many references can only be created after all records have been processed, so we can't resolve them here.
            // Only the number of resolved references is returned.
            if ($mapEntry->isManyToManyRelation()) {
                $mapResolved = [];
                $countResolved = 0;
                foreach ($rawValue as $refId) {
                    try {
                        $resolvedId = $this->resolveReference($model, $refId, $mapEntry);
                        if ($resolvedId) {
                            ++$countResolved;
                        }
                        $mapResolved[$refId] = $resolvedId;
                    } catch (ReferenceNotFoundException $e) {
                        $this->logger->warning('Many-to-many relation not resolved (will try again after all records are imported): ' . $e->getMessage());
                    }
                }
                $model->addManyToManyReferences($mapEntry->getTargetField(), $mapResolved);
                return $countResolved;
            }
            $stringValue = implode('-', $rawValue);
        } else {
            $stringValue = (string)$rawValue;
        }
        if ($foreignMatchField === 'uid') {
            return (int)$stringValue;
        }
        $isNullable = $mapEntry->isNullable();
        if (empty($stringValue) || (!empty($mapEntry->get('exclude_values')) && in_array($stringValue, $mapEntry->get('exclude_values'), true))) {
            return $isNullable ? null : 0;
        }
        // The uid of inline fields points to the translated uid, not the original uid
        $inverseTypeIsInline = ($mapEntry->get('inverse')['type']??null) === 'inline';
        $languageId = $inverseTypeIsInline && $model->getLanguageUid() > 0 ? $model->getLanguageUid() : 0;
        $referenceId = null;
        try {
            $referenceId = $this->mapping->getTargetReferenceId($targetTable, $foreignMatchField, $stringValue, $languageId);
        } catch (ReferenceNotFoundException) {
            if ($this->addTableFieldReferenceMapping($targetTable, $foreignMatchField)) {
                $referenceId = $this->mapping->getTargetReferenceId($targetTable, $foreignMatchField, $stringValue, $languageId);
            }
        }
        if ($referenceId === null) {
            // inline records cannot be added if the parent record is not yet imported.
            // this indicates that either the import priority is wrong or that the target record does not exist any more
            // These records cannot be imported
            if ($inverseTypeIsInline) {
                throw new RecordInvalidException(sprintf('The record %s.%s: %s cannot be imported because an inline parent reference to %s.%s; %s was not found.',
                    $model->getTargetTable(),
                    $model->getSourceRecordIdentifier(),
                    $model->getSourceRecordIdentifier(),
                    $targetTable,
                    $foreignMatchField,
                    $stringValue
                ));
            }
            if ($mapEntry->get('on_reference_not_found') === 'set_empty_value') {
                return $isNullable ? null : 0;
            }
            $this->mapping->addUnresolvedReference($rawValue, $model, $mapEntry);
        }
        return $referenceId;
    }

    /**
     * Initialize the mapping of dependencies between records.
     * @param array<string, string[]> $dependenciesToOtherTables
     */
    public function mapDependencies(array $dependenciesToOtherTables): void
    {
        foreach ($dependenciesToOtherTables as $table => $tableFields) {
            foreach ($tableFields as $field) {
                $this->addTableFieldReferenceMapping($table, $field);
            }
        }
    }

    /**
     * Adds a mapping of table field references to identifiers in the import target record mapping.
     *
     * This method checks if the given table and field are already mapped in the provided target record mapping.
     * If not, it retrieves records from the specified table and processes them to populate the mapping with
     * identifier references. It supports tables with language fields by filtering for default-language records
     * if applicable.
     *
     * @param string $table The name of the database table to retrieve records from.
     * @param string $field The table field to be used for generating identifier references.
     * @return bool Returns true if the mapping was added; returns false if the mapping already existed
     */
    protected function addTableFieldReferenceMapping(string $table, string $field): bool
    {
        if (!$this->mapping->hasTableMappingBeenInitialized($table, $field)) {
            $this->mapping->setTableMappingInitialized($table, $field);
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
            $fields = ['uid', $field];
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
            if ($languageField) {
                $fields[] = $languageField;
            }
            $queryBuilder->select(...$fields);
            $queryBuilder->from($table);
            $result = $queryBuilder->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $languageId = $languageField ? (int)$row[$languageField] : 0;
                $this->mapping->addIdentifierReference($table, $field, (string)$row[$field], (int)$row['uid'], $languageId);
            }
            $result->free();
            return true;
        }
        return false;
    }

    /**
     * Try to resolve references that were not resolved during the import process.
     * This can happen if a reference is not found in the import target record mapping,
     * e.g. because the referenced record was not yet imported during the import process.
     *
     * @param ImportTargetRecordMapping $mapping The reference resolver instance containing the mapping and references.
     *
     * @return void
     */
    public function tryResolvingUnresolvedReferences(ImportTargetRecordMapping $mapping): void
    {
        foreach ($mapping->getUnresolvedReferences() as $entry) {
            $model = $entry['model'];
            $mapEntry = $entry['mapEntry'];
            $rawValue = $entry['rawValue'];
            $targetTable = $model->getTargetTable();
            $targetField = $mapEntry->getTargetField();
            $targetUid = $model->getTargetRecordId();
            try {
                $referenceId = $this->resolveReference($model, $rawValue, $mapEntry);
                if ($referenceId !== null) {
                    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                    $queryBuilder = $connectionPool->getQueryBuilderForTable($targetTable);
                    $queryBuilder->update($targetTable)
                        ->set($targetField, $referenceId)
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($targetUid, Connection::PARAM_INT)));
                    $queryBuilder->executeStatement();
                }
            } catch (ReferenceNotFoundException) {
                $referenceId = null;
            }
            if ($referenceId === null) {
                $this->logReferenceError($model, $rawValue, $mapEntry);
            }
        }
        $this->mapping->clearUnresolvedReferences();
    }

    /**
     * Logs an error message related to unresolved table reference records during the import process.
     *
     * This method generates an error log that provides details about unresolved references
     * in the import operation. It includes information about source and target records,
     * reference tables, and fields that caused the error. The message can be formatted
     * as critical or non-critical based on the input parameter.
     *
     * @param ImportRecordModel $model The import record model containing details about the target record being processed.
     * @param mixed $rawValue The raw value of the reference field causing the unresolved reference error.
     * @param ImportFieldConfigurationModel $mapEntry The configuration of the field mapping associated with the reference.
     * @return void No return value. Logs the error message using the logger.
     */
    protected function logReferenceError(ImportRecordModel $model, mixed $rawValue, ImportFieldConfigurationModel $mapEntry): void
    {
        $targetTable = $model->getTargetTable();
        $targetField = $mapEntry->getTargetField();
        $targetUid = $model->getTargetRecordId();
        $message = 'The table reference %s.%s: %s has references to unresolved records in "%s.%s". Source identifier: %s. Target uid: %s';
        $referenceTable = $mapEntry->getReferenceTable();
        $errorMessage = sprintf(
            $message,
            $referenceTable,
            $mapEntry->get('foreign_match_field'),
            $rawValue,
            $targetTable,
            $targetField,
            $model->getSourceRecordIdentifier(),
            $targetUid,
        );
        $this->logger->warning($errorMessage);
    }
}
