<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\TargetResolution;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use BrainAppeal\CampusEventsConnector\Import\Repository\AbstractImportRowRepository;
use BrainAppeal\CampusEventsConnector\Import\Exception\ReferenceNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ProcessingResult;
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
     * Sets the target record UID list for processed records based on the given import data and source type.
     *
     * This method determines the mapping of unique target identifiers from the provided import data and updates
     * the processing result with the processed record IDs. It handles different unique target identifier fields
     * based on the source type's data transformer configuration.
     *
     * @param ImportTableConfigurationModel $importConfiguration The import configuration for the target table.
     * @param array<string, ImportRecordModel> $modelsByKeyAndLanguage Import models mapped by unique target identifier and language.
     * @param array<string, string> $importKeyByCompositeKey Mapping of unique target identifiers to import keys.
     * @return ImportRecordModel[] Mapped models with target record IDs set.
     */
    public function mapInsertedModels(ImportTableConfigurationModel $importConfiguration, array $modelsByKeyAndLanguage, array $importKeyByCompositeKey): array
    {
        if (empty($modelsByKeyAndLanguage)) {
            return [];
        }
        $targetTable = $importConfiguration->getTableName();
        $uniqueTargetIdentifierField = $importConfiguration->getSourceIdentifierField();
        $mappedModels = [];
        if ($uniqueTargetIdentifierField === 'uid') {
            foreach ($modelsByKeyAndLanguage as $transformedModel) {
                $uid = (int)$transformedModel->getTransformedData()['uid'];
                $transformedModel->setTargetRecordId($uid);
                $mappedModels[] = $transformedModel;
                $this->mapping->addProcessed($targetTable, $uid, true, true);
                if (!empty($mmReferences = $transformedModel->getManyToManyReferences())) {
                    $this->mapping->addManyToManyReference($targetTable, $uid, $mmReferences);
                }
            }
        } else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connectionPool->getQueryBuilderForTable($targetTable);
            $fields = ['uid', $uniqueTargetIdentifierField];
            $languageField = $GLOBALS['TCA'][$targetTable]['ctrl']['languageField'] ?? null;
            if ($languageField) {
                $fields[] = $languageField;
            }
            $queryBuilder->select(...$fields);
            $queryBuilder->from($targetTable);
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    $uniqueTargetIdentifierField,
                    $queryBuilder->createNamedParameter($importKeyByCompositeKey, Connection::PARAM_STR_ARRAY)
                )
            );
            $result = $queryBuilder->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $languageId = $languageField ? $row[$languageField] : 0;
                $sourceRecordId = (string)$row[$uniqueTargetIdentifierField];
                $keyWithLanguage = sprintf('%s:%d', $sourceRecordId, $languageId);
                $transformedModel = $modelsByKeyAndLanguage[$keyWithLanguage] ?? null;
                if ($transformedModel) {
                    $uid = (int)$row['uid'];
                    $transformedModel->setTargetRecordId($uid);
                    $this->mapping->addProcessed($targetTable, $uid, true, true);
                    $this->mapping->addIdentifierReference($targetTable, $uniqueTargetIdentifierField, $sourceRecordId, $uid, $languageId);
                    $persistData = $transformedModel->getTransformedData();
                    $persistData['uid'] = $uid;
                    $transformedModel->setPersistedData($persistData);
                    if (!empty($mmReferences = $transformedModel->getManyToManyReferences())) {
                        $this->mapping->addManyToManyReference($targetTable, $uid, $mmReferences);
                    }
                    $mappedModels[] = $transformedModel;
                }
            }
        }
        return $mappedModels;
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
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId);
        $queryBuilder->select(ImportRecordModel::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING, 'sys_language_uid', 'target_record_uid')
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
        }
        $result->free();
    }

    /**
     * Resolves a raw value to a corresponding target value based on mapping rules.
     * Handles unresolved references by either marking them or assigning a default value.
     *
     * @param ImportRecordModel $model The import record model containing the data.
     * @param mixed $rawValue The raw value to resolve.
     * @param ImportFieldConfigurationModel|array $mapEntry Mapping information including reference and target details.
     *
     * @return int|null The resolved value, or null if the value is nullable and unresolved.
     */
    public function resolve(ImportRecordModel $model, mixed $rawValue, ImportFieldConfigurationModel|array $mapEntry): ?int
    {
        try {
            $rawValue = $this->resolveReference($model, $rawValue, $mapEntry);
        } catch (ReferenceNotFoundException) {
            $model->addUnresolvedReference($mapEntry->getReferenceTable(), $mapEntry->getTargetField(), $rawValue);
            $rawValue = $mapEntry->isNullable() ? null : 0;
        }
        return $rawValue;
    }

    /**
     * Resolves a reference ID based on the provided raw value, mapping entry, and target record mapping.
     *
     * @param ImportRecordModel $model
     * @param mixed $rawValue The raw value to be resolved, which can be a scalar or an array.
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel|array<string, mixed> $mapEntry An associative array defining the reference table and foreign match field.
     * @return int|null The resolved reference ID as an integer if found; null if no matching record exists.
     *
     * @throws ReferenceNotFoundException If the foreign match field is not defined in the mapping entry.
     */
    protected function resolveReference(ImportRecordModel $model, mixed $rawValue, ImportFieldConfigurationModel|array $mapEntry): ?int
    {
        $targetTable = $mapEntry->getReferenceTable();
        $foreignMatchField = $mapEntry->get('foreign_match_field');
        if (empty($foreignMatchField)) {
            throw new ReferenceNotFoundException(sprintf('Can\'t resolve reference for table field "%s.%s": No foreign match field defined.', $model->getTargetTable(), $mapEntry->getTargetField()), 1751549547);
        }
        if (is_array($rawValue)) {
            // Many-to-many references can only be created after all records have been processed, so we can't resolve them here.
            // Only the number of resolved references is returned.
            if ($mapEntry->has('mm_table')) {
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
        try {
            $referenceId = $this->mapping->getTargetReferenceId($targetTable, $foreignMatchField, $stringValue, $languageId);
        } catch (ReferenceNotFoundException) {
            $this->addTableFieldReferenceMapping($targetTable, $foreignMatchField);
            $referenceId = $this->mapping->getTargetReferenceId($targetTable, $foreignMatchField, $stringValue, $languageId);
        }
        if ($referenceId === null) {
            if ($mapEntry->get('on_reference_not_found') === 'set_empty_value') {
                return $isNullable ? null : 0;
            }
            throw new ReferenceNotFoundException(sprintf('Can\'t resolve reference for table field "%s.%s": No matching record found for "%s" in "%s.%s"', $model->getTargetTable(), $mapEntry->getTargetField(), $stringValue, $targetTable, $foreignMatchField), 1751549547);
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
     * @return void
     */
    protected function addTableFieldReferenceMapping(string $table, string $field): void
    {
        if (!$this->mapping->hasTableFieldMap($table, $field)) {
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
        }
    }

    /**
     * Logs unresolved references found in the processing result. For each model with unresolved references, this method retrieves
     * the appropriate data transformer, identifies the target table, and logs an error message detailing the issue.
     *
     * @param ProcessingResult $processingResult The ProcessingResult object containing details about models with unresolved references.
     *                                           Each model is expected to support methods for accessing the target record ID and unresolved references.
     * @return void
     */
    public function logUnresolvedReferences(ProcessingResult $processingResult): void
    {
        foreach ($processingResult->getModelsWithUnresolvedReferences() as $targetTable => $importModels) {
            $tableFieldReferences = [];
            foreach ($importModels as $model) {
                $unresolvedReferences = $model->getUnresolvedReferences();
                foreach ($unresolvedReferences as $table => $tableReferences) {
                    $tableFieldReferences[$table] = [];
                    foreach ($tableReferences as $targetField => $rawValue) {
                        $tableFieldReferences[$table][$targetField][$rawValue]['uids'][] = $model->getTargetRecordId();
                        $tableFieldReferences[$table][$targetField][$rawValue]['source_ids'][] = $model->getSourceRecordIdentifier();
                    }
                }
            }
            foreach ($tableFieldReferences as $table => $references) {
                foreach ($references as $targetField => $referenceValues) {
                    foreach ($referenceValues as $rawValue => $uidMap) {
                        $errorMessage = sprintf(
                            'The model %s has references to unresolved records in "%s.%s". Value: %s. Source records: %s. Target records: %s',
                            $targetTable,
                            $table,
                            $targetField,
                            $rawValue,
                            implode(', ', $uidMap['source_ids']),
                            implode(', ', $uidMap['uids'])
                        );
                        $this->logger->error($errorMessage, $tableFieldReferences);
                    }
                }
            }
        }
    }
}
