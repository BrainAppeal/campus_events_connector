<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Configuration;

use BrainAppeal\CampusEventsConnector\Import\Exception\ImportTcaConfigurationException;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class ImportTableConfigurationProvider
{
    /**
     * @var array<string, ImportTableConfigurationModel>
     */
    private array $configurationCache = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function hasImportConfiguration(string $table): bool
    {
        return !empty($GLOBALS['TCA'][$table][ImportTableConfigurationModel::TCA_IMPORT_KEY]);
    }

    /**
     * @return \Doctrine\DBAL\Schema\Column[]
     */
    public function getTableColumns(string $table): array
    {
        $connection = $this->connectionPool->getConnectionForTable($table);
        return $connection->createSchemaManager()->listTableColumns($table);
    }

    /**
     * Retrieves the column names of a specified database table.
     *
     * @param string $table The name of the database table to retrieve column names from.
     * @return array<string, string> An associative array where the keys are the lowercase column names,
     *               and the values are the original column names as they appear in the database.
     */
    public function getTableColumnNames(string $table): array
    {
        $columnNames = [];
        foreach ($this->getTableColumns($table) as $column) {
            $name = $column->getName();
            $columnNames[strtolower($name)] = $name;
        }
        return $columnNames;
    }

    /**
     * Retrieves the import configuration for the specified table from the TCA.
     *
     * @param string $table Name of the table for which the import configuration is retrieved.
     * @return array<string, mixed> The import configuration associated with the specified table.
     * @throws ImportTcaConfigurationException If the import configuration is missing or empty.
     */
    protected static function getImportConfiguration(string $table): array
    {
        $importConfiguration = $GLOBALS['TCA'][$table][ImportTableConfigurationModel::TCA_IMPORT_KEY] ?? [];
        if (empty($importConfiguration)) {
            throw new ImportTcaConfigurationException('Can\'t get import configuration for table "' . $table . '": TCA import configuration missing.', 1751549546);
        }
        return $importConfiguration;
    }

    /**
     * Retrieves the import group key for the given table.
     * Generates the key based on the table name if not explicitly defined in the configuration.
     *
     * @param string $table The table name for which the import group key is being retrieved.
     * @param array|null $configuration Optional configuration array. If not provided, it will be fetched internally.
     * @return string The calculated or retrieved import group key for the table.
     * @throws ImportTcaConfigurationException
     */
    public static function getImportGroupKeyForTable(string $table, ?array $configuration = null): string
    {
        if (!$configuration) {
            $configuration = self::getImportConfiguration($table);
        }
        $importGroupKey = $configuration['importGroupKey'] ?? null;
        if (empty($importGroupKey)) {
            $tableNameParts = explode('_', str_replace('tx_', '', $table));
            $importGroupKey = $tableNameParts[0];
        }
        if (mb_strlen($importGroupKey) > 64) {
            $importGroupKey = mb_substr($importGroupKey, 0, 64, 'UTF-8');
        }
        return (string)$importGroupKey;
    }

    /**
     * Retrieves the configuration for the specified table.
     *
     * @param string $table The name of the table for which to retrieve the configuration.
     * @return ImportTableConfigurationModel The configuration object for the specified table.
     * @throws ImportTcaConfigurationException If the configuration for the table cannot be retrieved or is invalid.
     */
    public function getConfiguration(string $table): ImportTableConfigurationModel
    {
        if (!isset($this->configurationCache[$table])) {
            $importConfiguration = self::getImportConfiguration($table);
            $importGroupKey = self::getImportGroupKeyForTable($table, $importConfiguration);
            $this->configurationCache[$table] = new ImportTableConfigurationModel(
                $table,
                $importGroupKey,
                $importConfiguration,
                $this->getTableColumns($table),
            );
        }

        return $this->configurationCache[$table];
    }
}
