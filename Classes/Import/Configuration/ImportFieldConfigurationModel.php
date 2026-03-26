<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Configuration;

use BrainAppeal\CampusEventsConnector\Import\Exception\ImportTcaConfigurationException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Model for import field configuration
 */
class ImportFieldConfigurationModel
{
    protected array $configuration = [];

    /**
     * @param string $table
     * @param string $importKey
     * @param string $column
     * @param array<string, mixed> $tcaConfiguration
     * @param ?Column $columnInfo
     */
    public function __construct(
        string $table,
        private readonly string $importKey,
        private readonly string $column,
        array $tcaConfiguration,
        ?Column $columnInfo = null
    ) {
        $this->initializeConfiguration($table, $tcaConfiguration, $columnInfo);
    }

    /**
     * Initializes the configuration for a given table and updates the internal state
     * based on the provided configuration and column information.
     *
     * @param string $table The name of the database table being configured.
     * @param array<string, mixed> $config The configuration settings for the table column.
     * @param ?Column $columnInfo Optional additional metadata for the column, including type, length, nullable status, and other properties.
     *
     * @return void
     */
    private function initializeConfiguration(string $table, array $config, ?Column $columnInfo): void
    {
        $tcaImportKey = $this->importKey;
        $importItem = $config[$tcaImportKey];
        $importItem['target_field'] = $this->column;
        $importItem = $this->addReferenceMapping($table, $this->column, $importItem, $config);
        $importItem['filterMultiByte4'] = true;
        if ($columnInfo) {
            $dbType = Type::lookupName($columnInfo->getType());
            if ($columnInfo->hasPlatformOption('charset')) {
                $charset = $columnInfo->getPlatformOption('charset');
                $importItem['charset'] = $charset;
                if ($charset === 'utf8mb4') {
                    $importItem['filterMultiByte4'] = false;
                }
            }
            if (empty($importItem['normalizer'])) {
                $importItem['normalizer'] = $dbType;
                $configType = $config['config']['type'] ?? null;
                if ($configType === 'check') {
                    $importItem['normalizer'] = 'bool_as_int';
                }
            }
            $importItem['is_nullable'] = !$columnInfo->getNotnull();
            $importItem['db_type'] = $dbType;
            $importItem['length'] = $columnInfo->getLength();
            $default = $config['config']['default'] ?? null;
            if (!$importItem['is_nullable']) {
                $default = $columnInfo->getDefault();
                if ($dbType === 'integer') {
                    $default = (int)$default;
                }
            }
            $importItem['default'] = $default;
        }
        $this->configuration = $importItem;
    }

    public function getTargetField(): string
    {
        return $this->column;
    }

    public function getSourceField(): string
    {
        $field = $this->configuration['field'] ?? '';
        if (is_array($field)) {
            return $field[0];
        }
        return (string)$field;
    }

    public function getFieldOrFieldList(): string|array
    {
        return ($this->configuration['field'] ?? '');
    }

    public function getReferenceTable(): string
    {
        return (string)($this->configuration['reference_table'] ?? '');
    }

    public function isReference(): bool
    {
        return !empty($this->getReferenceTable()) && !empty($this->getFieldOrFieldList());
    }

    public function getType(): string
    {
        return (string)($this->configuration['type'] ?? '');
    }

    public function getDbType(): string
    {
        return (string)($this->configuration['db_type'] ?? '');
    }

    public function getLength(): int
    {
        return (int)($this->configuration['length'] ?? 0);
    }

    /**
     * Retrieves the data collection normalizer value from the configuration.
     *
     * @return string|null The collection normalizer value if set, or null if not defined.
     */
    public function getDataCollectionNormalizer(): ?string
    {
        return $this->configuration['collection_normalizer'] ?? null;
    }

    public function getDataTransformationNormalizer(): string
    {
        return (string)($this->configuration['normalizer'] ?? '');
    }

    public function isNullable(): bool
    {
        return (bool)($this->configuration['is_nullable'] ?? false);
    }

    public function getDefault(): mixed
    {
        return $this->configuration['default'] ?? null;
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
     * Determines if the current configuration represents a many-to-many relationship.
     *
     * @return bool True if a many-to-many relationship is defined, otherwise false.
     */
    public function isManyToManyRelation(): bool
    {
        return !empty($this->configuration['mm_table']);
    }

    /**
     * Retrieves the name of the many-to-many relation table, if defined in the configuration.
     *
     * @return ?string The name of the many-to-many table or null if not configured.
     */
    public function getManyToManyTable(): ?string
    {
        return $this->configuration['mm_table'] ?? null;
    }

    /**
     * Initializes the import field map definition for the given table
     *
     * @param string $table The table name
     * @return array<string, mixed> The field map containing mapping definitions and additional table column information.
     * @throws ImportTcaConfigurationException
     */
    private function addReferenceMapping(string $table, string $column, array $importItem, array $config): array
    {
        $foreignTable = $importItem['reference_table'] ?? ($config['config']['foreign_table'] ?? null);
        if (empty($foreignTable)) {
            return $importItem;
        }
        $foreignColumns = $GLOBALS['TCA'][$foreignTable]['columns'] ?? [];
        $importItem['reference_table'] = $foreignTable;
        $importItem['maxitems'] = (int)max(1, $config['config']['maxitems'] ?? 1);
        $foreignMatchField = $importItem['foreign_match_field'] ?? null;
        if (empty($foreignMatchField)) {
            $foreignMatchField = ImportTableConfigurationModel::DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD;
            $targetImportConfiguration = $GLOBALS['TCA'][$foreignTable][$this->importKey] ?? [];
            if (!empty($targetImportConfiguration['referenceUid'])) {
                $foreignMatchField = $targetImportConfiguration['referenceUid'];
            }
            $importItem['foreign_match_field'] = $foreignMatchField;
        }
        if ($foreignMatchField !== ImportTableConfigurationModel::DEFAULT_UNIQUE_TARGET_IDENTIFIER_FIELD && !isset($foreignColumns[$foreignMatchField])) {
            throw new ImportTcaConfigurationException(sprintf('Foreign match field "%s" does not exist in table "%s" for field "%s.%s"', $foreignMatchField, $foreignTable, $table, $column), 1766932979);
        }
        foreach ($foreignColumns as $foreignColumn => $foreignColumnConfig) {
            $foreignTable = $foreignColumnConfig['config']['foreign_table'] ?? '';
            $foreignMatchField = $foreignColumnConfig['config']['foreign_field'] ?? '';
            if ($foreignTable === $table && $foreignMatchField === $column) {
                // The inverse type is needed for the reference mapping for translated records
                $importItem['inverse'] = [
                    'field' => $foreignColumn,
                    'type' => $foreignColumnConfig['config']['type'] ?? '',
                ];
            }
        }
        if (!empty($config['config']['MM'])) {
            $importItem['mm_table'] = $config['config']['MM'];
        }
        return $importItem;
    }
}
