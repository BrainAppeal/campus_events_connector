<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2025 Brain Appeal GmbH
 *
 * @copyright 2025 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Utility;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides utility methods for generating and retrieving field map definitions for database table columns.
 * The field map incorporates default settings, extended metadata (data type, length, etc.), and custom configurations
 * from the TCA (Table Configuration Array).
 */
class TableFieldMapHelper
{
    /**
     * The processed import field map for this type. The default settings are extended with the data type and the
     * max length from the database schema
     *
     * @var array<string, array{target_field: string, field: string, type: string, db_type: string, length: int}>
     */
    protected static array $importFieldMap = [];

    /**
     * Retrieves the import field map definition for the given table, initializing it if it has not been set.
     *
     * @param string $table The table name
     * @param string $process
     * @return array<string, array<string, mixed>> The field map containing mapping definitions and additional table column information.
     */
    public static function getImportFieldMap(string $table, string $process = 'default'): array
    {
        if (!isset(static::$importFieldMap[$table])) {
            static::$importFieldMap[$table] = self::createImportTableFieldMaps($table);
        }

        return static::$importFieldMap[$table][$process];
    }

    /**
     * Initializes the import field map definition for the given table
     *
     * @param string $table The table name
     * @return array<string, array<string, mixed>> The field map containing mapping definitions and additional table column information.
     */
    private static function createImportTableFieldMaps(string $table): array
    {
        $tcaColumns = $GLOBALS['TCA'][$table]['columns'] ?? [];
        if (!$tcaColumns) {
            throw new \RuntimeException('Can\'t get import field map for table "' . $table . '": TCA columns configuration missing.', 1751549547);
        }
        $tcaImportKey = TCAUtility::TCA_IMPORT_KEY;
        $importFieldMaps = [];
        $filteredTcaColumns = array_filter($tcaColumns, static fn($config) => !empty($config[$tcaImportKey]));
        if (empty($filteredTcaColumns)) {
            throw new \RuntimeException(sprintf('No import configuration exists for table "%s": Add %s setting to the TCA columns.', $table, $tcaImportKey), 1751549548);
        }
        $tableColumInfo = self::getTableColumnInfo($table);
        foreach ($filteredTcaColumns as $column => $config) {
            $importItem = $config[$tcaImportKey];
            $importItem['target_field'] = $column;
            if ($columnInfo = $tableColumInfo[$column] ?? null) {
                if (empty($importItem['type'])) {
                    $importItem['type'] = $columnInfo['type'];
                    $configType = $config['config']['type'] ?? null;
                    if ($configType === 'check') {
                        $importItem['type'] = 'bool_as_int';
                    }
                }
                $importItem['db_type'] = $columnInfo['type'];
                $importItem['length'] = $columnInfo['length'];
                $importItem['default'] = $config['config']['default'] ?? null;
            }
            $itemProcess = $config[$tcaImportKey]['custom_process'] ?? 'default';
            $importFieldMaps[$itemProcess][$column] = $importItem;
        }
        return $importFieldMaps;
    }

    /**
     * Retrieves information about the columns of a database table.
     *
     * @param string $table The table name
     * @return array An associative array where the keys are column names and the values are arrays containing
     *               column details such as 'name', 'type', and 'length'.
     * @throws Exception
     */
    private static function getTableColumnInfo(string $table): array
    {
        /** @var ConnectionPool $pool */
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $pool->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        $tableColumInfo = $schemaManager->listTableColumns($table);
        $mapColumnInfo = [];
        foreach ($tableColumInfo as $column) {
            $name = $column->getName();
            $mapColumnInfo[$name] = [
                'name' => $name,
                'type' => Type::lookupName($column->getType()),
                'length' => $column->getLength(),
            ];
        }
        return $mapColumnInfo;
    }
}
