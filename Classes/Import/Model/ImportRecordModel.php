<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;

/**
 * Class ImportRecordModel
 *
 * Represents a model for importing records, providing properties and methods to manage and manipulate the imported data.
 */
class ImportRecordModel
{
    public const UNIQUE_SOURCE_IDENTIFIER_FIELD_INT = 'source_record_uid';
    public const UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING = 'source_record_identifier';

    /**
     * @var int<1, max>|null The uid of the record. The uid is only unique in the context of the database table.
     */
    protected ?int $uid = null;

    /**
     * @var int The language id
     */
    protected int $languageUid = 0;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     */
    protected ?int $pid = null;

    /**
     * @var int<0, max>|null The id of the import record this row belongs to
     */
    protected ?int $importId = null;

    /**
     * @var int
     */
    protected int $crdate = 0;

    /**
     * @var int
     */
    protected int $lastUpdated = 0;

    /**
     * @var int
     */
    protected int $priority = 0;

    /**
     * @var bool
     */
    protected bool $importDataCleared = false;

    /**
     * @var array
     */
    protected array $importData;

    /**
     * @var string
     */
    protected string $internalImportData;

    /**
     * @var string
     */
    protected string $targetTable;

    /**
     * Some import types have string identifiers, so we need to separate ID fields for string and int identifiers
     * This ensures the type compatibility of the fields in SQL queries
     * @var string
     */
    protected string $sourceRecordIdentifier;

    /**
     * Identifier field used for integer identifiers
     * This ensures the type compatibility of the fields in SQL queries
     * @var int
     */
    protected int $sourceRecordUid = 0;

    /**
     * @var int
     */
    protected int $targetRecordId = 0;

    /**
     * Internal flag to prevent saving of import records
     * @var bool
     */
    protected bool $isInvalidated = false;

    /**
     * @var bool
     */
    protected bool $dataFullyLoaded = false;

    /**
     * @var bool
     */
    protected bool $dataProcessed = false;

    /**
     * @var bool
     */
    protected bool $filesProcessed = false;

    /**
     * @var bool
     */
    protected bool $importSkipped = false;

    /**
     * @var bool
     */
    protected bool $unchanged = false;

    /**
     * @var string
     */
    protected string $dataHash = '';

    /**
     * @var ?array<string, mixed> The transformed data.
     */
    protected ?array $transformedData = null;

    /**
     * @var ?array<string, mixed> The original data from the database.
     */
    protected ?array $persistedData = null;

    /**
     * Unresolved reference mapping
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $unresolvedReferences = [];

    /**
     * Unresolved value mapping
     *
     * @var array<string, string>
     */
    protected array $unresolvedValues = [];
    /**
     * @var array<string, array<int, ?int>>
     */
    private array $manyToManyReferences = [];

    /**
     * Constructor method to initialize the object with import data, source type, source record identifier,
     * and an optional data hash.
     *
     * @param string|array $importData The data to be imported.
     * @param string $targetTable The target table for the import source
     * @param int|string $sourceRecordIdentifier The identifier of the source record.
     * @param string|null $dataHash An optional hash of the data.
     */
    public function __construct(string|array $importData, string $targetTable, int|string $sourceRecordIdentifier, ?string $dataHash = null)
    {
        $this->targetTable = $targetTable;
        if (is_int($sourceRecordIdentifier)) {
            $this->sourceRecordUid = $sourceRecordIdentifier;
            $this->sourceRecordIdentifier = (string)$sourceRecordIdentifier;
        } else {
            $this->sourceRecordUid = 0;
            $this->sourceRecordIdentifier = $sourceRecordIdentifier;
        }
        if ($dataHash) {
            $this->dataHash = $dataHash;
        }
        $this->updateImportData($importData, false);
    }

    /**
     * Retrieves a list of unresolved values.
     *
     * @return array<string, string> The list of unresolved values.
     */
    public function getUnresolvedValues(): array
    {
        return $this->unresolvedValues;
    }

    /**
     * Adds an unresolved value to the internal collection for logging purposes.
     *
     * @param string $targetField The field in the table that the reference targets.
     * @param string $message Info message about the unresolved value.
     * @return void
     */
    public function addUnresolvedValue(string $targetField, string $message): void
    {
        $this->unresolvedValues[$targetField] = $message;
    }

    /**
     * Retrieves a list of unresolved references.
     *
     * @return array<string, array<string, mixed>> The list of unresolved references.
     */
    public function getUnresolvedReferences(): array
    {
        return $this->unresolvedReferences;
    }

    /**
     * Adds an unresolved reference to the internal collection for later resolution.
     *
     * @param string $table The name of the table associated with the unresolved reference.
     * @param string $targetField The field in the table that the reference targets.
     * @param mixed $rawValue The raw value of the unresolved reference.
     * @return void
     */
    public function addUnresolvedReference(string $table, string $targetField, mixed $rawValue): void
    {
        $this->unresolvedReferences[$table][$targetField] = $rawValue;
    }

    /**
     * Checks if there are any unresolved references.
     *
     * @return bool True if unresolved references exist; otherwise, false.
     */
    public function hasUnresolvedReferences(): bool
    {
        return count($this->unresolvedReferences) > 0;
    }

    /**
     * Determines and returns the unique source identifier field based on the provided identifier type.
     *
     * @param bool $useIntegerIdentifiers Indicates whether to use integer identifiers.
     *                                     If true, returns the integer field constant;
     *                                     otherwise, returns the string field constant.
     * @return string The unique source identifier field.
     */
    public static function getUniqueSourceIdentifierField(bool $useIntegerIdentifiers): string
    {
        return $useIntegerIdentifiers ? self::UNIQUE_SOURCE_IDENTIFIER_FIELD_INT : self::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(?int $pid): void
    {
        $this->pid = $pid;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function setLanguageUid(int $languageUid): void
    {
        $this->languageUid = $languageUid;
    }

    public function getImportId(): ?int
    {
        return $this->importId;
    }

    /**
     * Get crdate
     *
     * @return int
     */
    public function getCrdate(): int
    {
        return $this->crdate;
    }

    /**
     * Set crdate
     *
     * @param int $crdate
     */
    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(int $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Retrieves the import data.
     *
     * @return array<string, mixed> The import data, where keys represent identifiers and values represent the associated data.
     * @throws \LogicException If the import data has been cleared and the method is called afterward.
     */
    public function getImportData(): array
    {
        if ($this->importDataCleared) {
            throw new \LogicException('Import data has been cleared. This method cannot be called after the data has been cleared.');
        }
        return $this->importData;
    }

    /**
     * Clears the raw import data by resetting associated properties.
     * This saves a lot of memory when importing large amounts of data.
     *
     * @return void
     */
    public function clearRawData(): void
    {
        $this->importDataCleared = true;
        $this->internalImportData = '';
        $this->importData = [];
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getTargetTable(): string
    {
        return $this->targetTable;
    }

    /**
     * Get source record identifier. Only used if the identifier is a string. Otherwise, getSourceRecordUid is used
     * This is used internally, so we have consistent identifier types
     *
     * @return string
     */
    public function getSourceRecordIdentifier(): string
    {
        return $this->sourceRecordIdentifier;
    }

    /**
     * Get source record uid. Return 0 if the identifier is a string.
     * This is only used for SQL queries
     *
     * @return int
     */
    public function getSourceRecordUid(): int
    {
        return $this->sourceRecordUid;
    }

    /**
     * Get targetRecordId
     *
     * @return int
     */
    public function getTargetRecordId(): int
    {
        return $this->targetRecordId;
    }

    /**
     * Set targetRecordId
     *
     * @param int $targetRecordId
     */
    public function setTargetRecordId(int $targetRecordId): void
    {
        $this->targetRecordId = $targetRecordId;
    }

    /**
     * Get dataFullyLoaded
     *
     * @return bool
     */
    public function getDataFullyLoaded(): bool
    {
        return $this->dataFullyLoaded;
    }

    /**
     * Set dataFullyLoaded
     *
     * @param bool $dataFullyLoaded
     */
    public function setDataFullyLoaded(bool $dataFullyLoaded): void
    {
        $this->dataFullyLoaded = $dataFullyLoaded;
    }

    /**
     * Get dataProcessed
     *
     * @return bool
     */
    public function getDataProcessed(): bool
    {
        return $this->dataProcessed;
    }

    /**
     * Set dataProcessed
     *
     * @param bool $dataProcessed
     */
    public function setDataProcessed(bool $dataProcessed): void
    {
        $this->dataProcessed = $dataProcessed;
    }

    /**
     * Get filesProcessed
     *
     * @return bool
     */
    public function getFilesProcessed(): bool
    {
        return $this->filesProcessed;
    }

    /**
     * Set files processed
     *
     * @param bool $filesProcessed
     */
    public function setFilesProcessed(bool $filesProcessed): void
    {
        $this->filesProcessed = $filesProcessed;
    }

    public function isImportSkipped(): bool
    {
        return $this->importSkipped;
    }

    public function isUnchanged(): bool
    {
        return $this->unchanged;
    }

    /**
     * Get data hash
     *
     * @return string
     */
    public function getDataHash(): string
    {
        return $this->dataHash;
    }

    /**
     * Get transformed data after they were set by the transformer.
     *
     * @return array<string, mixed>
     */
    public function getTransformedData(): array
    {
        if ($this->transformedData === null) {
            throw new \LogicException('Transformed data not available yet. This must only be called after the data has been transformed.');
        }
        return $this->transformedData;
    }

    /**
     * Set transformed data
     */
    public function setTransformedData(?array $transformedData): void
    {
        $this->transformedData = $transformedData;
    }

    public function getPersistedData(): array
    {
        if ($this->transformedData === null) {
            throw new \LogicException('Transformed data not available yet. This must only be called after the original data have been hydrated from the database.');
        }
        return $this->persistedData;
    }

    public function setPersistedData(?array $persistedData): void
    {
        $this->persistedData = $persistedData;
    }

    /**
     * Converts the object data into an associative array with various properties and metadata.
     *
     * @param bool $skipUpdateFields Indicates whether to skip the update fields such as creation date (crdate)
     *                               and timestamp (tstamp). If true, the creation date will default to the current time.
     * @return array<string, mixed> An associative array containing the object data and metadata.
     */
    public function toArray(bool $skipUpdateFields = false): array
    {
        $data = [
            'import_data' => $this->internalImportData,
            'data_fully_loaded' => $this->getDataFullyLoaded(),
            'source_type' => $this->getTargetTable(),
            self::UNIQUE_SOURCE_IDENTIFIER_FIELD_STRING => $this->getSourceRecordIdentifier(),
            self::UNIQUE_SOURCE_IDENTIFIER_FIELD_INT => $this->getSourceRecordUid(),
            'target_record_uid' => $this->getTargetRecordId(),
            'data_processed' => $this->getDataProcessed(),
            'files_processed' => $this->getFilesProcessed(),
            'import_skipped' => $this->isImportSkipped(),
            'unchanged' => $this->isUnchanged(),
            'data_hash' => $this->getDataHash(),
            'priority' => $this->getPriority(),
            'sys_language_uid' => $this->languageUid,
            'last_updated' => $this->lastUpdated,
        ];
        if (!$skipUpdateFields) {
            $data['crdate'] = $this->getCrdate();
            $data['tstamp'] = $this->lastUpdated > 0 ? $this->lastUpdated : $this->getCrdate();
        } else {
            $data['crdate'] = $this->lastUpdated > 0 ? $this->lastUpdated : time();
        }
        return $data;
    }

    /**
     * Updates the current object properties with values from the provided array.
     *
     * @param array<string, mixed> $row Associative array containing key-value pairs used to update the object's properties.
     *                   Expected keys include:
     *                   - 'uid' (int): The unique identifier.
     *                   - 'pid' (int): The parent identifier.
     *                   - 'crdate' (int): The creation date.
     *                   - 'priority' (int): The priority value.
     *                   - 'import_id' (int): The identifier for the import.
     *                   - 'data_processed' (bool): Indicates if the data has been processed.
     *                   - 'files_processed' (bool): Indicates if the files have been processed.
     *                   - 'data_fully_loaded' (bool): Indicates if the data has been fully loaded.
     *                   - 'import_skipped' (bool): Indicates if the import was skipped.
     *                   - 'target_record_uid' (int): The target record unique identifier.
     */
    public function updateFromArray(array $row): void
    {
        $this->uid = (int)$row['uid'];
        $this->pid = (int)$row['pid'];
        $this->crdate = (int)$row['crdate'];
        $this->priority = (int)$row['priority'];
        $this->importId = (int)$row['import_id'];
        $this->dataProcessed = (bool)$row['data_processed'];
        $this->filesProcessed = (bool)$row['files_processed'];
        $this->dataFullyLoaded = (bool)$row['data_fully_loaded'];
        $this->importSkipped = (bool)$row['import_skipped'];
        $this->unchanged = (bool)$row['unchanged'];
        $this->targetRecordId = (int)$row['target_record_uid'];
        $this->lastUpdated = (int)$row['last_updated'];
        if (isset($row['sys_language_uid'])) {
            $this->languageUid = (int)$row['sys_language_uid'];
        }

    }

    /**
     * Updates the import data and optionally overwrites the data hash.
     *
     * @param string|array $importData The new import data, either as a JSON string or an associative array.
     * @param bool $overwriteHash Determines whether to overwrite the existing data hash. Defaults to true.
     */
    public function updateImportData(string|array $importData, bool $overwriteHash = true): void
    {
        if (is_array($importData)) {
            $this->importData = $importData;
            $this->internalImportData = json_encode($importData);
        } else {
            $this->importData = json_decode($importData, true);
            $this->internalImportData = $importData;
        }
        if (!$this->dataHash || $overwriteHash) {
            // Don't include child records in the hash, so changes in child records will not trigger a false update required
            // Also exclude keys that start with an underscore
            $scalarData = array_filter(
                $this->importData,
                static function ($value, $key) {
                    return is_scalar($value) && !str_starts_with($key, ImportDataTransformerInterface::INTERNAL_KEY_PREFIX);
                },
                ARRAY_FILTER_USE_BOTH
            );

            // Sort by key to ensure that the sorting of the fields does not influence the hash result
            ksort($scalarData);
            $this->dataHash = hash('sha512', $this->internalImportData);
        }
    }

    public function hasData(): bool
    {
        return !empty($this->importData);
    }

    public function isInvalidated(): bool
    {
        return $this->isInvalidated;
    }

    public function setIsInvalidated(bool $isInvalidated): void
    {
        $this->isInvalidated = $isInvalidated;
    }

    /**
     * Retrieves a list of many-to-many references.
     *
     * @return array<string, array<int, ?int>> The list of many-to-many references.
     */
    public function getManyToManyReferences(): array
    {
        return $this->manyToManyReferences;
    }

    /**
     * @param string $field
     * @param array<int, ?int> $mapReferences
     * @return void
     */
    public function addManyToManyReferences(string $field, array $mapReferences): void
    {
        $this->manyToManyReferences[$field] = $mapReferences;
    }
}
