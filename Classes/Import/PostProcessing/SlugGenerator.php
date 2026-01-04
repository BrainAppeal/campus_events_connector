<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\PostProcessing;

use BrainAppeal\CampusEventsConnector\Import\Utility\ImportSlugHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fills table slug fields with a proper value
 */
class SlugGenerator
{
    /**
     * Retrieves an instance of SlugHelper configured for the specified slug field, if available.
     *
     * @param string $slugField The name of the slug field to generate SlugHelper for. Defaults to 'slug'.
     * @return ImportSlugHelper|null An instance of SlugHelper if the configuration exists, or null otherwise.
     */
    protected function getSlugHelper(string $table, string $slugField = 'slug'): ?ImportSlugHelper
    {
        $slugFieldConfig = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'] ?? null;
        if ($slugFieldConfig !== null) {
            return GeneralUtility::makeInstance(ImportSlugHelper::class, $table, $slugField, $slugFieldConfig);
        }
        return null;
    }

    /**
     * Checks if the specified table has a slug field with the provided name.
     *
     * @param string $table The name of the database table to check.
     * @param string $slugField The name of the slug field to check for. Defaults to 'slug'.
     * @return bool Returns true if the slug field exists and is configured; otherwise, returns false.
     */
    public function hasSlugField(string $table, string $slugField = 'slug'): bool
    {
        return !empty($GLOBALS['TCA'][$table]['columns'][$slugField]['config'] ?? null);
    }

    /**
     * Populates or updates slugs for records in a specific table based on the slug field configuration.
     *
     * @param string $table The name of the database table for which slugs should be generated.
     * @param array<int> $onlyForRecordIds An optional list of record IDs to restrict slug generation/update. Defaults to an empty array, meaning all records are processed.
     * @param string $slugField The name of the slug field in the table. Defaults to 'slug'.
     */
    public function populateSlugs(string $table, array $onlyForRecordIds = [], string $slugField = 'slug'): void
    {
        $slugHelper = $this->getSlugHelper($table, $slugField);
        if ($slugHelper === null) {
            // Table has no slug field
            return;
        }
        $connection = $this->getDatabaseConnection($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select('*')
            ->from($table);
        if (!empty($onlyForRecordIds)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($onlyForRecordIds, Connection::PARAM_INT_ARRAY)));
        }
        $statement = $queryBuilder
            ->addOrderBy('pid', 'asc')
            ->addOrderBy('uid', 'asc')
            ->executeQuery();

        $tableData = [];
        while ($record = $statement->fetchAssociative()) {
            $recordId = (int)$record['uid'];
            $pid = (int)$record['pid'];
            $oldSlug = $record[$slugField];
            $slug = $slugHelper->generate($record, $pid);

            $state = RecordStateFactory::forName($table)
                ->fromArray($record, $pid, $recordId);
            if (!$slugHelper->isUniqueInTable($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInTable($slug, $state);
            }
            if ($oldSlug !== $slug) {
                $slugHelper->setTableSlug($recordId, $slug, $state->getContext()->getLanguageId());
                $tableData[$recordId] = [$slugField => $slug];
                if (count($tableData) > 10) {
                    $this->updateSlugsWithDataHandler($table, $tableData);
                    $tableData = [];
                }
            }
        }
        if (count($tableData) > 0) {
            $this->updateSlugsWithDataHandler($table, $tableData);
        }
    }

    /**
     * Updates the slugs for the specified table using the provided data via the DataHandler.
     *
     * @param string $table The name of the database table whose slugs need to be updated.
     * @param array $tableData The data to be used for updating the slugs, structured as key-value pairs.
     * @return void This method does not return a value.
     */
    private function updateSlugsWithDataHandler(string $table, array $tableData): void
    {
        // Get an instance of the DataHandler and process the data
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([$table => $tableData], []);
        $dataHandler->enableLogging = false;
        $dataHandler->process_datamap();
    }

    /**
     * @param string $table
     * @return Connection
     */
    protected function getDatabaseConnection(string $table): Connection
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($table);
    }
}
