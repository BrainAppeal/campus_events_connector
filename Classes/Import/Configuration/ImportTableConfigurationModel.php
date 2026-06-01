<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Configuration;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DefaultDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportTcaConfigurationException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Model for import table configuration
 */
class ImportTableConfigurationModel
{
    public const TCA_IMPORT_KEY = 'ce_import';

    /**
     * Defines the default field name used to identify unique target entities.
     */
    public const DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD = 'uid';

    private ?string $languageField;

    private ?ImportFieldConfigurationModel $languageFieldMapping = null;

    /**
     * The list of tables and fields, this data type has dependencies to.
     * @var ?array<string, string[]>
     */
    protected ?array $dependenciesToOtherTables = null;

    /**
     * This data type has internal dependencies to the current table, e.g., parent field.
     * The array contains the import source field names with the raw reference values.
     * This is used to determine the priority of a given import record.
     *
     * @var ?ImportFieldConfigurationModel[]
     */
    protected ?array $internalDependencies = null;

    /**
     * Indicates whether the current instance contains integer-based identifiers.
     * This is useful for determining the type or format of identifiers used
     * in the context of the instance.
     *
     * @var bool
     */
    private bool $hasIntegerIdentifiers;

    /**
     * Indicates whether the import field map has been initialized.
     * This flag determines if the mapping between import source fields
     * and target fields is ready for processing.
     *
     * @var bool
     */
    private bool $importFieldMapInitialized = false;

    /**
     * The processed import field map for this table. The default settings are extended with the data type and the
     * max length from the database schema
     *
     * @var array<string, array<string, ImportFieldConfigurationModel>>
     */
    protected array $importFieldMap = [];
    /**
     * Represents the field used to identify the source of the data.
     * This is typically a unique identifier specific to the source system.
     *
     * @var string
     */
    private string $sourceIdentifierField;
    /**
     * @var ?string
     */
    private ?string $deletedField = null;
    /**
     * @var ?string
     */
    private ?string $transOrigPointerField;

    /**
     * @param string $tableName
     * @param array<string, mixed> $configuration
     * @param array<string, Column> $tableColumnInfo
     */
    public function __construct(
        private readonly string $tableName,
        private readonly string $importGroupKey,
        private readonly array $configuration,
        private readonly array $tableColumnInfo
    ) {
        $this->languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null;
        $this->deletedField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? null;
        $this->transOrigPointerField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? null;
        $this->sourceIdentifierField = (string)($this->configuration['referenceUid'] ?? self::DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD);
        if ($this->sourceIdentifierField === self::DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD) {
            $this->hasIntegerIdentifiers = true;
        } else {
            $type = Type::lookupName($tableColumnInfo[$this->sourceIdentifierField]->getType());
            $this->hasIntegerIdentifiers = $type === 'integer';
        }
    }

    public function getImportGroupKey(): string
    {
        return $this->importGroupKey;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Retrieves the source identifier field from the configuration.
     * This is the field where identifier values from the import source are stored.
     *
     * @return string The source identifier field, or the default unique target identifier field if not set.
     */
    public function getSourceIdentifierField(): string
    {
        return $this->sourceIdentifierField;
    }

    public function hasIntegerIdentifiers(): bool
    {
        return $this->hasIntegerIdentifiers;
    }

    /**
     * Retrieves the import field configuration for a specified field and process.
     *
     * @param string $fieldName The name of the field to retrieve the configuration for.
     * @param string $process The process identifier (default is 'default').
     * @return ?ImportFieldConfigurationModel The configuration for the specified field and process, or null if not found.
     */
    public function getImportConfigurationForField(string $fieldName, string $process = 'default'): ?ImportFieldConfigurationModel
    {
        return $this->getImportFieldMap($process)[$fieldName] ?? null;
    }

    /**
     * Retrieves the import field map definition for the given table, initializing it if it has not been set.
     *
     * @param string $process Fields can be configured to be processed in different ways. This parameter allows to retrieve the field map for a specific process.
     * @return array<string, ImportFieldConfigurationModel> The field map containing mapping definitions and additional table column information.
     */
    public function getImportFieldMap(string $process = 'default'): array
    {
        if (!$this->importFieldMapInitialized) {
            $this->importFieldMap = $this->createImportTableFieldMaps();
        }
        return $this->importFieldMap[$process] ?? [];
    }

    public function hasLanguageField(): bool
    {
        return !empty($this->languageField);
    }

    public function getLanguageField(): ?string
    {
        return $this->languageField;
    }

    public function getTransOrigPointerField(): ?string
    {
        return $this->transOrigPointerField;
    }

    public function getDeletedField(): ?string
    {
        return $this->deletedField;
    }

    public function getLanguageFieldMapping(): ?ImportFieldConfigurationModel
    {
        if ($this->languageField && $this->languageFieldMapping === null) {
            $this->languageFieldMapping = $this->getImportConfigurationForField($this->languageField);
        }
        return $this->languageFieldMapping;
    }

    /**
     * Name of the API endpoint to fetch data from.
     */
    public function getApiEndpoint(): ?string
    {
        return $this->configuration['apiEndpoint'] ?? null;
    }

    /**
     * Name of the API endpoint parameter to use for filtering.
     */
    public function getApiFilterParameter(): ?string
    {
        return $this->configuration['apiFilterParameter'] ?? null;
    }

    /**
     * Indicates whether the import identifier field is mapped to the uid field.
     */
    public function isImportIdMappedToUidField(): bool
    {
        return $this->getSourceIdentifierField() === self::DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD;
    }

    public function getImportField(): string
    {
        return (string)($this->configuration['importField'] ?? '');
    }

    /**
     * Optional field for the import source in the target tables.
     * This enables support for multiple import sources for the same target table.
     */
    public function getTargetImportSourceField(): ?string
    {
        return $this->configuration['targetImportSourceField'] ?? null;
    }

    public function isApiListItemContainsAllData(): bool
    {
        return (bool)($this->configuration['apiListItemContainsAllData'] ?? false);
    }

    public function getAttributeList(): string
    {
        return (string)($this->configuration['attributeList'] ?? '');
    }

    public function getDataTransformerClass(): string
    {
        return $this->configuration['dataTransformerClass'] ?? DefaultDataTransformer::class;
    }

    public function getPriority(): int
    {
        return (int)($this->configuration['priority'] ?? 100);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->configuration[$key]);
    }

    /**
     * Initializes the import field map definition for the given table
     *
     * @return array<string, array<string, ImportFieldConfigurationModel>> The field map containing mapping definitions and additional table column information.
     */
    private function createImportTableFieldMaps(): array
    {
        $table = $this->tableName;
        $tcaColumns = $GLOBALS['TCA'][$table]['columns'] ?? [];
        if (!$tcaColumns) {
            throw new ImportTcaConfigurationException('Can\'t get import field map for table "' . $table . '": TCA columns configuration missing.', 1751549547);
        }
        $tcaImportKey = self::TCA_IMPORT_KEY;
        $importFieldMaps = [];
        $filteredTcaColumns = array_filter($tcaColumns, static fn($config) => !empty($config[$tcaImportKey]));
        if (empty($filteredTcaColumns)) {
            throw new ImportTcaConfigurationException(sprintf('No import configuration exists for table "%s": Add %s setting to the TCA columns.', $table, $tcaImportKey), 1751549548);
        }
        $mapColumnsByImportField = [];
        foreach ($filteredTcaColumns as $column => $config) {
            $importItem = $config[$tcaImportKey];
            if (empty($config['config']['foreign_table']) && !empty($importItem['field']) && is_string($importItem['field'])) {
                $mapColumnsByImportField[$importItem['field']] = $column;
            }
        }
        $tableColumInfo = $this->tableColumnInfo;
        foreach ($filteredTcaColumns as $column => $config) {
            $importItem = $config[$tcaImportKey];
            if (!empty($config['config']['foreign_table']) && !empty($importItem['field']) && is_scalar($importItem['field'])) {
                $config[$tcaImportKey]['raw_value_field'] = $mapColumnsByImportField[(string)$importItem['field']] ?? null;
            }
            $fieldConfigurationModel = new ImportFieldConfigurationModel($table, $tcaImportKey, $column, $config, $tableColumInfo[$column] ?? null);
            $itemProcess = $config[$tcaImportKey]['custom_process'] ?? 'default';
            $importFieldMaps[$itemProcess][$column] = $fieldConfigurationModel;
        }
        return $importFieldMaps;
    }

    /**
     * Initializes dependencies based on the provided import field map.
     * Scans the field map for reference entries and categorizes them as
     * dependencies to other tables or internal dependencies within the same table.
     */
    private function initializeDependencies(): void
    {
        $this->dependenciesToOtherTables = [];
        $this->internalDependencies = [];
        $fieldMap = $this->getImportFieldMap();
        foreach ($fieldMap as $mapEntry) {
            if ($mapEntry->isReference()) {
                $matchField = $mapEntry->get('foreign_match_field');
                $referenceTable = $mapEntry->getReferenceTable();
                if ($referenceTable !== $this->tableName) {
                    $this->dependenciesToOtherTables[$referenceTable][$matchField] = $matchField;
                } else {
                    $this->internalDependencies[] = $mapEntry;
                }
            }
        }
    }

    /**
     * Returns the table field list of dependencies to other tables.
     *
     * @return array<string, string[]>
     */
    public function getDependenciesToOtherTables(): array
    {
        if ($this->dependenciesToOtherTables === null) {
            $this->initializeDependencies();
        }
        return $this->dependenciesToOtherTables;
    }

    /**
     * Returns the list of internal dependencies within the current table.
     * @return ImportFieldConfigurationModel[]
     */
    public function getInternalDependencies(): array
    {
        if ($this->internalDependencies === null) {
            $this->initializeDependencies();
        }
        return $this->internalDependencies;
    }

    /**
     * Retrieves the list of fields to be ignored during comparison processes.
     * These fields include predefined system fields and any additional fields
     * specified in the configuration under 'excludeFromUpdateFields'.
     *
     * @return string[] The array of field names to be ignored during comparison.
     */
    public function getCompareIgnoreFields(): array
    {
        $compareIgnoreFields = ['uid', 'crdate', 'tstamp', 'deleted', 'hidden'];
        // Additional exclude fields from TCA configuration
        if ($excludeFromUpdateFields = ($this->configuration['excludeFromUpdateFields'] ?? [])) {
            $compareIgnoreFields = array_merge($compareIgnoreFields, $excludeFromUpdateFields);
        }
        return $compareIgnoreFields;
    }
}
