<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Writer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use BrainAppeal\CampusEventsConnector\Import\Exception\ValidationException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AdvancedBulkReplaceService
 *
 * Provides an advanced bulk replacement functionality in a database with
 * enhanced features such as batch processing, error handling, and various
 * modes for handling duplicate keys. It offers methods for generating SQL
 * statements and executing them efficiently, including
 * - REPLACE INTO
 * - INSERT ON DUPLICATE KEY UPDATE
 * - INSERT IGNORE
 * This class manages data validation, dynamic query generation, parameter
 * binding, and transaction handling allowing robust and safe database operations.
 */
class AdvancedBulkReplaceService
{
    /**
     * Enhanced bulk replace with batch processing and error handling
     */
    public function bulkReplace(
        string $tableName,
        array $data,
        array $columns = [],
        array $types = [],
        array $options = []
    ): array {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName);

        $defaultOptions = [
            'batchSize' => 950,
            'useTransaction' => true,
            'onDuplicateKey' => 'replace', // 'replace', 'update', 'ignore'
            'validateData' => true,
        ];

        $options = array_merge($defaultOptions, $options);

        if (empty($data)) {
            return ['affected' => 0, 'batches' => 0, 'errors' => []];
        }

        if ($options['validateData']) {
            $this->validateData($data, $columns);
        }

        if (empty($columns)) {
            $columns = array_keys(reset($data));
        }

        $results = [
            'affected' => 0,
            'batches' => 0,
            'errors' => [],
        ];

        // Process in batches
        $batches = array_chunk($data, $options['batchSize']);

        foreach ($batches as $batchIndex => $batch) {
            try {
                if ($options['useTransaction']) {
                    $connection->beginTransaction();
                }

                $affected = $this->processBatch(
                    $connection,
                    $tableName,
                    $batch,
                    $columns,
                    $types,
                    $options
                );

                if ($options['useTransaction']) {
                    $connection->commit();
                }

                $results['affected'] += $affected;
                $results['batches']++;

            } catch (\Exception $e) {
                /** @phpstan-ignore if.alwaysTrue */
                if ($options['useTransaction']) {
                    $connection->rollBack();
                }

                $results['errors'][] = [
                    'batch' => $batchIndex,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process a single batch
     */
    private function processBatch(
        Connection $connection,
        string $tableName,
        array $batch,
        array $columns,
        array $types,
        array $options
    ): int {
        switch ($options['onDuplicateKey']) {
            case 'update':
                return $this->executeInsertOnDuplicateKeyUpdate($connection, $tableName, $batch, $columns, $types);
            case 'ignore':
                return $this->executeInsertIgnore($connection, $tableName, $batch, $columns, $types);
            case 'replace':
            default:
                return $this->executeReplaceInto($connection, $tableName, $batch, $columns, $types);
        }
    }

    /**
     * Execute REPLACE INTO statement
     */
    private function executeReplaceInto(
        Connection $connection,
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): int {
        $sql = $this->buildReplaceIntoSQL($tableName, $batch, $columns, $types);
        return $connection->executeStatement($sql['query'], $sql['parameters'], $sql['types']);
    }

    /**
     * Execute INSERT ... ON DUPLICATE KEY UPDATE
     */
    private function executeInsertOnDuplicateKeyUpdate(
        Connection $connection,
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): int {
        $sql = $this->buildInsertOnDuplicateKeyUpdateSQL($tableName, $batch, $columns, $types);
        return $connection->executeStatement($sql['query'], $sql['parameters'], $sql['types']);
    }

    /**
     * Execute INSERT IGNORE
     */
    private function executeInsertIgnore(
        Connection $connection,
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): int {
        $sql = $this->buildInsertIgnoreSQL($tableName, $batch, $columns, $types);
        return $connection->executeStatement($sql['query'], $sql['parameters'], $sql['types']);
    }

    /**
     * Build REPLACE INTO SQL
     */
    private function buildReplaceIntoSQL(
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): array {
        $quotedColumns = array_map(static function ($col) {
            return "`$col`";
        }, $columns);

        /** @noinspection SqlResolve */
        $query = sprintf(
            'REPLACE INTO `%s` (%s) VALUES %s',
            $tableName,
            implode(', ', $quotedColumns),
            $this->buildValuesPlaceholders(count($batch), count($columns))
        );

        return array_merge(['query' => $query], $this->buildParameters($batch, $columns, $types));
    }

    /**
     * Build INSERT ... ON DUPLICATE KEY UPDATE SQL
     */
    private function buildInsertOnDuplicateKeyUpdateSQL(
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): array {
        $quotedColumns = array_map(static function ($col) {
            return "`$col`";
        }, $columns);

        // Build UPDATE clauses for the duplicate key
        $updateClauses = [];
        foreach ($columns as $column) {
            if ($column !== 'uid') { // Don't update UID on duplicate
                $updateClauses[] = "`$column` = VALUES(`$column`)";
            }
        }

        /** @noinspection SqlResolve */
        $query = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $tableName,
            implode(', ', $quotedColumns),
            $this->buildValuesPlaceholders(count($batch), count($columns)),
            implode(', ', $updateClauses)
        );

        return array_merge(['query' => $query], $this->buildParameters($batch, $columns, $types));
    }

    /**
     * Build INSERT IGNORE SQL
     */
    private function buildInsertIgnoreSQL(
        string $tableName,
        array $batch,
        array $columns,
        array $types
    ): array {
        $quotedColumns = array_map(static function ($col) {
            return "`$col`";
        }, $columns);

        /** @noinspection SqlResolve */
        $query = sprintf(
            'INSERT IGNORE INTO `%s` (%s) VALUES %s',
            $tableName,
            implode(', ', $quotedColumns),
            $this->buildValuesPlaceholders(count($batch), count($columns))
        );

        return array_merge(['query' => $query], $this->buildParameters($batch, $columns, $types));
    }

    /**
     * Build the parameter array
     */
    private function buildParameters(array $batch, array $columns, array $types): array
    {
        $parameters = [];
        $parameterTypes = [];

        foreach ($batch as $row) {
            foreach ($columns as $column) {
                $value = $row[$column] ?? null;
                $parameters[] = $value;
                $parameterTypes[] = $types[$column] ?? $this->detectParameterType($value);
            }
        }

        return [
            'parameters' => $parameters,
            'types' => $parameterTypes,
        ];
    }

    /**
     * Build VALUES placeholders
     */
    private function buildValuesPlaceholders(int $rowCount, int $columnCount): string
    {
        $rowPlaceholder = '(' . str_repeat('?,', $columnCount - 1) . '?)';
        return str_repeat($rowPlaceholder . ',', $rowCount - 1) . $rowPlaceholder;
    }

    /**
     * Validate data structure
     */
    private function validateData(array $data, array $columns): void
    {
        if (empty($columns)) {
            $firstRow = reset($data);
            if (!is_array($firstRow)) {
                throw new ValidationException('Data must be array of arrays', 1735485450);
            }
        }

        foreach ($data as $index => $row) {
            if (!is_array($row)) {
                throw new ValidationException("Row $index must be an array", 1735485451);
            }
        }
    }

    /**
     * Detect parameter type
     */
    private function detectParameterType($value): int
    {
        if ($value === null) {
            return ParameterType::NULL;
        }
        if (is_int($value)) {
            return ParameterType::INTEGER;
        }
        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }
        if (is_array($value)) {
            return ArrayParameterType::STRING;
        }
        return ParameterType::STRING;
    }
}
