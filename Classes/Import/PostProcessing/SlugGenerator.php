<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\PostProcessing;

use BrainAppeal\CampusEventsConnector\Import\Utility\ImportSlugHelper;
use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fills table slug fields with a proper value
 */
class SlugGenerator
{
    /**
     * Checks if the specified table has any slug field
     *
     * @param string $table The name of the database table to check.
     * @return bool Returns true if the slug field exists and is configured; otherwise, returns false.
     */
    public function hasSlugField(string $table): bool
    {
        foreach ($GLOBALS['TCA'][$table]['columns'] ?? [] as $config) {
            if (!empty($config['config']['type']) && $config['config']['type'] === 'slug') {
                return true;
            }
        }
        return false;
    }

    /**
     * Populates or updates slugs for records in a specific table based on the slug field configuration.
     *
     * @param string $table The name of the database table for which slugs should be generated.
     * @param array<int> $onlyForRecordIds An optional list of record IDs to restrict slug generation/update. Defaults to an empty array, meaning all records are processed.
     * @param bool $useDataHandlerForSlugUpdates
     * @throws Exception
     * @throws SiteNotFoundException
     */
    public function populateSlugs(string $table, array $onlyForRecordIds = [], bool $useDataHandlerForSlugUpdates = true): void
    {
        foreach ($GLOBALS['TCA'][$table]['columns'] ?? [] as $column => $config) {
            if (!empty($config['config']['type']) && $config['config']['type'] === 'slug') {
                $this->populateSlugField($table, $column, $useDataHandlerForSlugUpdates, $onlyForRecordIds);
            }
        }
    }

    /**
     * Populates or updates slugs for records in a specific table based on the slug field configuration.
     *
     * @param string $table The name of the database table for which slugs should be generated.
     * @param string $slugField The name of the slug field in the table. Defaults to 'slug'.
     * @param bool $useDataHandlerForSlugUpdates
     * @param array<int> $onlyForRecordIds An optional list of record IDs to restrict slug generation/update. Defaults to an empty array, meaning all records are processed.
     * @throws Exception
     * @throws SiteNotFoundException
     */
    private function populateSlugField(string $table, string $slugField, bool $useDataHandlerForSlugUpdates = true, array $onlyForRecordIds = []): void
    {
        $slugFieldConfig = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'] ?? null;
        if ($slugFieldConfig === null) {
            // The given field is not a slug field, so we can skip it
            return;
        }
        $slugHelper = GeneralUtility::makeInstance(ImportSlugHelper::class, $table, $slugField, $slugFieldConfig);
        /** @var ImportSlugHelper $slugHelper */
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
        $persistMaxCount = $useDataHandlerForSlugUpdates ? 10 : 100;
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
                if (count($tableData) > $persistMaxCount) {
                    $this->updateSlugs($table, $tableData, $useDataHandlerForSlugUpdates);
                    $tableData = [];
                }
            }
        }
        if (count($tableData) > 0) {
            $this->updateSlugs($table, $tableData, $useDataHandlerForSlugUpdates);
        }
    }

    /**
     * Updates the slugs for the specified table using the provided data via the DataHandler.
     *
     * @param string $table The name of the database table whose slugs need to be updated.
     * @param array $tableData The data to be used for updating the slugs, structured as key-value pairs.
     * @param bool $useDataHandlerForSlugUpdates Either use the DataHandler to update the slugs, or update them directly via the database connection. Defaults to true.
     * @return void This method does not return a value.
     */
    private function updateSlugs(string $table, array $tableData, bool $useDataHandlerForSlugUpdates): void
    {
        if ($useDataHandlerForSlugUpdates) {
            // Get an instance of the DataHandler and process the data
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([$table => $tableData], []);
            $dataHandler->enableLogging = false;
            $dataHandler->process_datamap();
        } else {
            $connection = $this->getDatabaseConnection($table);
            foreach ($tableData as $recordId => $data) {
                $connection->update($table, $data, ['uid' => $recordId]);
            }
        }
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
