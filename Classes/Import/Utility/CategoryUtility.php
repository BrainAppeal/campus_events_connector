<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class CategoryUtility
{

    public static function getCategoryUidByTitle(string $title, ?int $createIfNotFoundPid = null, ?int $parentCategoryUid = null): ?int
    {
        // Search for category in sys_category table
        $category = self::getCategory($title);

        if ($category) {
            return $category['uid'];
        }
        if ($createIfNotFoundPid) {
            self::addCategory($title, $createIfNotFoundPid, $parentCategoryUid);
            return self::getCategoryUidByTitle($title, null, $parentCategoryUid);
        }
        return null;
    }

    private static function getCategory(string $title): array|false
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->select('uid', 'title')
            ->from('sys_category')
            ->setMaxResults(1)
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($title))
            )
            ->executeQuery()->fetchAssociative();
    }

    private static function addCategory(String $title, int $pid, ?int $parentCategoryUid = null): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $data = [
            'sys_category' => [
                StringUtility::getUniqueId('NEW') => [
                    'pid' => $pid,
                    'parent' => (int)$parentCategoryUid,
                    'title' => $title,
                ],
            ],
        ];

        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    public static function addReference(int $recordUid, int $categoryUid, string $table, string $fieldName = 'categories'): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $mmTable = 'sys_category_record_mm';
        $checkQb = $connectionPool->getQueryBuilderForTable($mmTable);
        $checkQb->select('uid_local')
            ->from($mmTable)
            ->where($checkQb->expr()->eq('uid_foreign', $checkQb->createNamedParameter($recordUid, Connection::PARAM_INT)))
            ->where($checkQb->expr()->eq('uid_local', $checkQb->createNamedParameter($categoryUid, Connection::PARAM_INT)))
            ->andWhere($checkQb->expr()->eq('tablenames', $checkQb->createNamedParameter($table)))
            ->andWhere($checkQb->expr()->eq('fieldname', $checkQb->createNamedParameter($fieldName)))
            ->setMaxResults(1);
        $existingEntry = $checkQb->executeQuery()->fetchAssociative();
        if (!empty($existingEntry)) {
            return;
        }
        $queryBuilder = $connectionPool->getQueryBuilderForTable($mmTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->insert($mmTable)
            ->values([
                'uid_foreign' => $recordUid,
                'uid_local' => $categoryUid,
                'tablenames' => $table,
                'fieldname' => $fieldName,
            ])
            ->executeStatement();
    }
}
