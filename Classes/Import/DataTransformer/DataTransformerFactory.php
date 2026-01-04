<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationProvider;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for DataTransformer classes
 */
class DataTransformerFactory implements SingletonInterface
{
    /**
     * @var array<string, ImportDataTransformerInterface>
     */
    protected array $registry = [];

    /**
     * @var array<string, string[]>
     */
    protected array $groupMap = [];

    /**
     * @var ?string
     */
    protected ?string $groupKey = null;

    /**
     * Constructor for initializing the class.
     *
     * Scans the global configuration for registered DataTransformers and registers
     * each DataTransformer with its corresponding import group key.
     */
    public function __construct(protected ImportTableConfigurationProvider $importTableConfigurationProvider)
    {
        $tableNames = array_keys($GLOBALS['TCA'] ?? []);
        foreach ($tableNames as $tableName) {
            if (!$importTableConfigurationProvider->hasImportConfiguration($tableName)) {
                continue;
            }
            $groupKey = $importTableConfigurationProvider->getImportGroupKeyForTable($tableName);
            $this->groupMap[$groupKey][$tableName] = $tableName;
        }
    }

    /**
     * Set the group key
     *
     * @param string $groupKey The group key
     */
    public function setGroupKey(string $groupKey): void
    {
        $this->groupKey = $groupKey;
        $this->initializeDataTransformerPriority();
    }

    /**
     * Get the current group key
     *
     * @return string The current group key
     */
    public function getGroupKey(): string
    {
        return $this->groupKey;
    }

    /**
     * Get the registered group keys
     *
     * @return string[] The registered group keys
     */
    public function getRegisteredGroupKeys(): array
    {
        return array_keys($this->groupMap);
    }

    /**
     * Get a DataTransformer instance by key
     *
     * @param string $targetTable The DataTransformer key
     * @param bool $ignoreGroupKey Whether to ignore the group key validation
     * @return ImportDataTransformerInterface The DataTransformer instance
     * @throws \InvalidArgumentException If no DataTransformer is registered for the given key
     */
    private function getDataTransformer(string $targetTable, bool $ignoreGroupKey = false): ImportDataTransformerInterface
    {
        if (!$ignoreGroupKey) {
            if (!$this->groupKey || empty($this->groupMap[$this->groupKey])) {
                throw new \InvalidArgumentException(
                    'The current group key for the import must be set',
                    1767537943
                );
            }

            // Check if the data transformer belongs to the current group
            if (!in_array($targetTable, $this->groupMap[$this->groupKey])) {
                throw new \InvalidArgumentException(
                    'DataTransformer with key ' . $targetTable . ' does not belong to the current group ' . $this->groupKey,
                    1767537944
                );
            }
        }

        if (!isset($this->registry[$targetTable])) {
            $importConfiguration = $this->importTableConfigurationProvider->getConfiguration($targetTable);
            $dataTransformerClass = $importConfiguration->getDataTransformerClass();
            if (!is_a($dataTransformerClass, ImportDataTransformerInterface::class, true)) {
                throw new \InvalidArgumentException(
                    sprintf('The DataTransformer registered for table %s must implement ImportDataTransformerInterface',  $targetTable),
                    1767537946
                );
            }
            $this->registry[$targetTable] = GeneralUtility::makeInstance($dataTransformerClass, $importConfiguration);
        }
        return $this->registry[$targetTable];
    }

    /**
     * Retrieve the registered keys based on the class map and group map.
     *
     * @return string[] An array of registered tables
     */
    public function getRegisteredTableNames(): array
    {
        return $this->groupMap[$this->groupKey] ?? [];
    }

    /**
     * Get all registered DataTransformer instances
     *
     * @return array<ImportDataTransformerInterface> The registered DataTransformer instances
     */
    public function getAll(): array
    {
        $instances = [];
        foreach ($this->getRegisteredTableNames() as $targetTable) {
            $instances[$targetTable] = $this->getDataTransformerByTable($targetTable);
        }
        return $instances;
    }

    /**
     * Check if a transformer exists for the specified table.
     *
     * @param string $tableName The name of the table to check.
     * @return bool Returns true if a transformer exists for the table, false otherwise.
     */
    public function hasImportConfigurationForTable(string $tableName): bool
    {
        return $this->importTableConfigurationProvider->hasImportConfiguration($tableName);
    }

    /**
     * Get a DataTransformer instance by type
     *
     * @param string $type The DataTransformer type (e.g. "Building", "Location")
     * @return ImportDataTransformerInterface The DataTransformer instance
     * @throws \InvalidArgumentException If no DataTransformer is registered for the given type
     */
    public function getDataTransformerByType(string $type): ImportDataTransformerInterface
    {
        return $this->getDataTransformer($type);
    }

    /**
     * Get a DataTransformer instance by table name
     *
     * @param string $tableName The DataTransformer table name
     * @param bool $ignoreGroupKey Whether to ignore the group key validation
     * @return ImportDataTransformerInterface The DataTransformer instance
     * @throws \InvalidArgumentException If no DataTransformer is registered for the given type
     */
    public function getDataTransformerByTable(string $tableName, bool $ignoreGroupKey = false): ImportDataTransformerInterface
    {
        return $this->getDataTransformer($tableName, $ignoreGroupKey);
    }


    /**
     * Initializes the priority of data transformers based on their dependencies and defined priorities.
     *
     * This method processes all available data transformers, calculates their priorities while taking
     * into account dependencies between transformers, and ensures that the priorities are adjusted
     * to satisfy dependency constraints. Higher priority numbers are interpreted as higher precedence.
     * The method also performs a safety check to prevent infinite loops caused by circular dependencies.
     *
     * @return void
     */
    private function initializeDataTransformerPriority(): void
    {
        $dataTransformers = $this->getAll();
        $dataTransformerPriority = [];
        $dependencies = [];
        $mapIdentifierFields = [];
        foreach ($dataTransformers as $dataTransformer) {
            $mapIdentifierFields[$dataTransformer->getTable()] = $dataTransformer->getImportConfiguration()->getSourceIdentifierField();
        }
        foreach ($dataTransformers as $dataTransformer) {
            $table = $dataTransformer->getTable();
            $dataTransformerPriority[$table] = $dataTransformer->getPriority(null);
            $refTables = [];
            foreach ($dataTransformer->getImportConfiguration()->getDependenciesToOtherTables() as $dependsOnTable => $fields) {
                if (isset($dependencies[$dependsOnTable][$table]) && in_array($table, $dependencies[$dependsOnTable], true)) {
                    throw new \RuntimeException('Circular dependency detected for data transformers: ' . $table . ' -> ' . $dependsOnTable);
                }
                // If the uid field is referenced and is used as the import identifier, we don't need to add the dependency'
                if (($mapIdentifierFields[$dependsOnTable]??null) !== 'uid' || count($fields) > 1 || current($fields) !== 'uid') {
                    $refTables[] = $dependsOnTable;
                }
            }
            $dependencies[$table] = $refTables;
        }

        $changed = true;
        $iteration = 0;
        // Limit iterations to prevent infinite loops in the case of circular dependencies
        while ($changed && $iteration < 20) {
            $changed = false;
            $iteration++;
            foreach ($dependencies as $table => $dependsOnTables) {
                foreach ($dependsOnTables as $dependsOnTable) {
                    // If they are the same, it's a self-dependency, which we can't resolve by priority
                    if ($dependsOnTable === $table) {
                        continue;
                    }
                    // Priority is DESC, so higher priority runs first
                    // We want $dependsOnTable to run BEFORE $table
                    // So $dependsOnTable must have HIGHER priority than $table
                    if (isset($dataTransformerPriority[$dependsOnTable]) && $dataTransformerPriority[$dependsOnTable] <= $dataTransformerPriority[$table]) {
                        $dataTransformerPriority[$dependsOnTable] = $dataTransformerPriority[$table] + 100;
                        $changed = true;
                    }
                }
            }
        }
        foreach ($dataTransformerPriority as $table => $priority) {
            $dataTransformer = $this->getDataTransformerByTable($table);
            $dataTransformer->setPriority($priority);
        }
    }
}
