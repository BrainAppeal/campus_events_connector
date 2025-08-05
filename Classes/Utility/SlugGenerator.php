<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Utility;

use BrainAppeal\CampusEventsConnector\Importer\PostImportHookInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fills table slug fields with a proper value
 */
class SlugGenerator implements PostImportHookInterface
{
    /**
     * @param int $pid The page id where the events are stored
     * @return bool
     */
    public function postImport(int $pid): bool
    {
        $this->populateSlugs(TCAUtility::TABLE_EVENTS);
        return true;
    }

    /**
     * Retrieves an instance of SlugHelper configured for the specified slug field, if available.
     *
     * @param string $slugField The name of the slug field to generate SlugHelper for. Defaults to 'slug'.
     * @return SlugHelper|null An instance of SlugHelper if the configuration exists, or null otherwise.
     */
    protected function getSlugHelper(string $table, string $slugField = 'slug'): ?SlugHelper
    {
        $slugFieldConfig = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'] ?? null;
        if ($slugFieldConfig !== null) {
            return GeneralUtility::makeInstance(SlugHelper::class, $table, $slugField, $slugFieldConfig);
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

        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$slugField]['config'];
        $evalInfo = !empty($fieldConfig['eval']) ? GeneralUtility::trimExplode(',', $fieldConfig['eval'], true) : [];
        $hasToBeUniqueInSite = in_array('uniqueInSite', $evalInfo, true);
        $hasToBeUniqueInPid = in_array('uniqueInPid', $evalInfo, true);
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class, $table, $slugField, $fieldConfig);

        while ($record = $statement->fetchAssociative()) {
            $recordId = (int)$record['uid'];
            $pid = (int)$record['pid'];
            $oldSlug = $record[$slugField];
            $slug = $slugHelper->generate($record, $pid);

            $state = RecordStateFactory::forName($table)
                ->fromArray($record, $pid, $recordId);
            if ($hasToBeUniqueInSite && !$slugHelper->isUniqueInSite($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInSite($slug, $state);
            }
            if ($hasToBeUniqueInPid && !$slugHelper->isUniqueInPid($slug, $state)) {
                $slug = $slugHelper->buildSlugForUniqueInPid($slug, $state);
            }
            if ($oldSlug !== $slug) {
                $connection->update(
                    $table,
                    [$slugField => $slug],
                    ['uid' => $recordId]
                );
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
