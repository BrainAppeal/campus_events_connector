<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\TargetResolution;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Exception\ReferenceNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;

/**
 * Handles the mapping between source records and target records during an import process.
 *
 * This class keeps track of relationships between source identifiers and their corresponding
 * target record IDs across different languages and source types. It also tracks processed
 * records during the import operation.
 */
final class ImportTargetRecordMapping
{
    /**
     * The ids are grouped by table, source identifier field, language, and source record id
     * The language id is required because inline fields are mapped to the translated uid, not the original uid
     *
     * @var array<string, array<string, array<int, array<string, int>>>>
     */
    private array $tableIdMap = [];

    /**
     * The uids of the records that have been skipped during the import process grouped by table
     * @var array<string, int[]>
     */
    private $skippedRowsByTable = [];
    /**
     * Map existing records loading indicators by table and field
     *
     * @var array<string, array<string, bool>>
     */
    private array $tableIdMapInitialized = [];

    /**
     * List of records that have unresolved references.
     *
     * @var array<array{rawValue: mixed, model: ImportRecordModel, mapEntry: ImportFieldConfigurationModel}>
     */
    protected array $unresolvedReferences = [];

    /**
     * Map import identifiers to uid value
     * This field is filled by \BrainAppeal\CampusEventsConnector\Import\Processing\TargetRecordPersister with the uid values of all
     * updated and inserted records
     *
     * @var array<string, int[]>
     */
    private array $mapProcessed = [];

    /**
     * The uid values of all updated and inserted records grouped by the target table
     * If the value is true, the record has been created, otherwise it has been updated
     * @var array<string, array<int, bool>>
     */
    private array $mapCreatedOrUpdated = [];

    /**
     * @var array<string, ManyToManyRelationMapping>
     */
    protected array $manyToManyReferences = [];

    public function addProcessed(
        string $targetTable,
        int    $targetRecordId,
        bool   $hasChanged,
        bool   $isNewRecord = false
    ): void
    {
        $this->mapProcessed[$targetTable][$targetRecordId] = $targetRecordId;
        if ($hasChanged) {
            $this->mapCreatedOrUpdated[$targetTable][$targetRecordId] = $isNewRecord;
        }
    }

    public function addIdentifierReference(
        string $table,
        string $sourceIdentifierField,
        int|string $sourceRecordId,
        int    $targetRecordId,
        int    $languageUid = 0
    ): void
    {
        $this->tableIdMap[$table][$sourceIdentifierField][$languageUid][(string)$sourceRecordId] = $targetRecordId;
    }

    /**
     * Adds an unresolved reference to the internal collection for further processing.
     *
     * @param mixed $rawValue
     * @param ImportRecordModel $model The import record model linked to the unresolved reference.
     * @param ImportFieldConfigurationModel $mapEntry The field configuration for the unresolved reference.
     *
     * @return void
     */
    public function addUnresolvedReference(
        mixed $rawValue,
        ImportRecordModel $model,
        ImportFieldConfigurationModel $mapEntry,
    ): void
    {
        $this->unresolvedReferences[] = [
            'rawValue' => $rawValue,
            'model' => $model,
            'mapEntry' => $mapEntry,
        ];
    }

    /**
     * Returns the list of unresolved references.
     * @return array<array{rawValue: mixed, model: ImportRecordModel, mapEntry: ImportFieldConfigurationModel}>
     */
    public function getUnresolvedReferences(): array
    {
        return $this->unresolvedReferences;
    }

    /**
     * Clears all unresolved references.
     *
     * @return void
     */
    public function clearUnresolvedReferences(): void
    {
        $this->unresolvedReferences = [];
    }

    /**
     * Checks if the table mapping has been initialized for the specified table and source identifier field.
     *
     * @param string $table The name of the table to check.
     * @param string $sourceIdentifierField The source identifier field to look for within the table.
     * @return bool True if the mapping exists, otherwise false.
     */
    public function hasTableMappingBeenInitialized(string $table, string $sourceIdentifierField): bool
    {
        return $this->tableIdMapInitialized[$table][$sourceIdentifierField] ?? false;
    }

    /**
     * Marks a specific table mapping as initialized.
     *
     * @param string $table The name of the table to be marked as initialized.
     * @param string $sourceIdentifierField The identifier field in the table to be marked as initialized.
     *
     * @return void
     */
    public function setTableMappingInitialized(string $table, string $sourceIdentifierField): void
    {
        $this->tableIdMapInitialized[$table][$sourceIdentifierField] = true;
        if (!isset($this->tableIdMap[$table][$sourceIdentifierField])) {
            $this->tableIdMap[$table][$sourceIdentifierField] = [];
        }
    }

    /**
     * Retrieves the target record ID for the specified table and source identifier field.
     * @param string $table
     * @param string $sourceIdentifierField
     * @param int|string $sourceRecordId
     * @param int $languageUid
     * @return int|null
     * @throws ReferenceNotFoundException If the target references for the specified table and field have not been initialized.
     */
    public function getTargetReferenceId(string $table, string $sourceIdentifierField, int|string $sourceRecordId, int $languageUid): ?int
    {
        if (!isset($this->tableIdMap[$table][$sourceIdentifierField])) {
            throw new ReferenceNotFoundException(sprintf('Target references for table %s and field %s have not been initialized', $table, $sourceIdentifierField));
        }
        return $this->tableIdMap[$table][$sourceIdentifierField][$languageUid][(string)$sourceRecordId] ?? null;
    }

    /**
     * Returns all the record IDs that require SQL post-processing
     *
     * @return array<string, int[]> An array of record IDs. Returns an empty array if all records of the source type should be processed.
     */
    public function getAllProcessed(): array
    {
        return $this->mapProcessed;
    }

    /**
     * Returns the number of created and updated records for each source type
     *
     * @return array<string, array{created: int, updated: int}> An array containing the number of created or updated entries.
     */
    public function getCountCreatedOrUpdatedByType(): array
    {
        return array_map(static function ($createdOrUpdated) {
            return [
                'created' => count(array_filter($createdOrUpdated, static fn($value) => $value === true)),
                'updated' => count(array_filter($createdOrUpdated, static fn($value) => $value === false)),
            ];
        }, $this->mapCreatedOrUpdated);
    }

    /**
     * Retrieves all entries that have been either created or updated.
     *
     * @return array<string, array<int, bool>> An array containing the created or updated entries.
     */
    public function getAllCreatedOrUpdated(): array
    {
        return $this->mapCreatedOrUpdated;
    }

    /**
     * Retrieves an array of many-to-many relationships.
     *
     * @return array An array containing the many-to-many references.
     */
    public function getManyToManyReferences(): array
    {
        return $this->manyToManyReferences;
    }

    /**
     * Adds many-to-many relationship references for a specified target table and record.
     *
     * @param string $targetTable The name or identifier of the target table.
     * @param int $targetRecordId The ID of the target record in the specified table.
     * @param array<string, int[]> $mmReferences An associative array of references where keys are field names and values are the references to be added.
     * @return void
     */
    public function addManyToManyReference(string $targetTable, int $targetRecordId, array $mmReferences): void
    {
        if (!isset($this->manyToManyReferences[$targetTable])) {
            $this->manyToManyReferences[$targetTable] = new ManyToManyRelationMapping($targetTable);
        }
        $this->manyToManyReferences[$targetTable]->addManyToManyReference($targetRecordId, $mmReferences);
    }

    public function getSkippedRowsByTable(string $table): array
    {
        return $this->skippedRowsByTable[$table] ?? [];
    }

    public function addSkippedUidForTable(string $table, int $uid): void
    {
        $this->skippedRowsByTable[$table][$uid] = true;
    }

}
