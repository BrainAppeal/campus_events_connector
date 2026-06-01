<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationProvider;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportTcaConfigurationException;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportContext;
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
     * @var array<string, bool>
     */
    protected array $groupInitializedMap = [];

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
     * @param string $importGroupKey The import group this table belongs to
     * @param bool $ignoreGroupKey Whether to ignore the group key validation
     * @return ImportDataTransformerInterface The DataTransformer instance
     * @throws ImportTcaConfigurationException
     */
    private function getDataTransformer(string $targetTable, string $importGroupKey, bool $ignoreGroupKey = false): ImportDataTransformerInterface
    {
        if (!$ignoreGroupKey) {
            if (!$importGroupKey || empty($this->groupMap[$importGroupKey])) {
                throw new \InvalidArgumentException(
                    'The current group key for the import must be set',
                    1767537943
                );
            }

            // Check if the data transformer belongs to the current group
            if (!in_array($targetTable, $this->groupMap[$importGroupKey])) {
                throw new \InvalidArgumentException(
                    'DataTransformer with key ' . $targetTable . ' does not belong to the current group ' . $importGroupKey,
                    1767537944
                );
            }
        }
        if ($importGroupKey && !isset($this->groupInitializedMap[$importGroupKey])) {
            $this->groupInitializedMap[$importGroupKey] = true;
            $this->initializeDataTransformerPriority($importGroupKey);
        }

        if (!isset($this->registry[$targetTable])) {
            $importConfiguration = $this->importTableConfigurationProvider->getConfiguration($targetTable);
            $dataTransformerClass = $importConfiguration->getDataTransformerClass();
            if (!is_a($dataTransformerClass, ImportDataTransformerInterface::class, true)) {
                throw new \InvalidArgumentException(
                    sprintf('The DataTransformer registered for table %s must implement ImportDataTransformerInterface', $targetTable),
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
     * @param string $importGroup
     * @return string[] An array of registered tables
     */
    public function getTableNamesForImportGroup(string $importGroup): array
    {
        return $this->groupMap[$importGroup] ?? [];
    }

    /**
     * Get all registered DataTransformer instances
     *
     * @param ImportContext $context
     * @return array<ImportDataTransformerInterface> The registered DataTransformer instances
     */
    public function getDataTransformersByContext(ImportContext $context): array
    {
        return $this->getDataTransformersForImportGroup($context->getImportSource());
    }
    /**
     * Get all registered DataTransformer instances
     *
     * @param string $importGroup
     * @return array<ImportDataTransformerInterface> The registered DataTransformer instances
     */
    public function getDataTransformersForImportGroup(string $importGroup): array
    {
        $instances = [];
        foreach ($this->getTableNamesForImportGroup($importGroup) as $targetTable) {
            $instances[$targetTable] = $this->getDataTransformer($targetTable, $importGroup, true);
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
     * Get a DataTransformer instance by table name
     *
     * @param string $tableName The DataTransformer table name
     * @param bool $ignoreGroupKey Whether to ignore the group key validation
     * @return ImportDataTransformerInterface The DataTransformer instance
     * @throws \InvalidArgumentException If no DataTransformer is registered for the given type
     */
    public function getDataTransformerByContextAndTable(ImportContext $context, string $tableName, bool $ignoreGroupKey = false): ImportDataTransformerInterface
    {
        return $this->getDataTransformer($tableName, $context->getImportSource(), $ignoreGroupKey);
    }

    /**
     * Initializes the priority of data transformers based on their dependencies and defined priorities.
     *
     * This method processes all available data transformers, calculates their priorities while taking
     * into account dependencies between transformers, and ensures that the priorities are adjusted
     * to satisfy dependency constraints. Higher priority numbers are interpreted as higher precedence.
     * The method also performs a safety check to prevent infinite loops caused by circular dependencies.
     *
     * @param string $importGroup
     * @throws ImportTcaConfigurationException
     */
    private function initializeDataTransformerPriority(string $importGroup): void
    {
        $dataTransformers = $this->getDataTransformersForImportGroup($importGroup);
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
                if (($mapIdentifierFields[$dependsOnTable] ?? null) !== 'uid' || count($fields) > 1 || current($fields) !== 'uid') {
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
            $dataTransformer = $this->getDataTransformer($table, $importGroup, true);
            $dataTransformer->setPriority($priority);
        }
    }
}
