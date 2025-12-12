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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportScheduleUtility implements SingletonInterface
{
    public const TABLE_IMPORT_ROW = 'tx_campuseventsconnector_import_schedule';

    public const IMPORT_TYPE_INSERT = 1;
    public const IMPORT_TYPE_UPDATE = 2;
    public const IMPORT_TYPE_NO_CHANGE = 0;

    /**
     * Local storage for schedule entries
     *
     * @var array
     */
    protected array $previousEntries = [];

    /**
     * Fetch old entry in schedule
     *
     * @param $itemId
     * @param $importType
     * @return mixed|null
     */
    public function fetchPreviousEntry($itemId, $importType)
    {
        if (!array_key_exists($importType, $this->previousEntries)) {
            $oldEntries = $this->fetchScheduleEntries($importType, false);
            $this->previousEntries[$importType] = [];
            foreach ($oldEntries as $entry) {
                $this->previousEntries[$importType][(int) $entry['import_uid']] = $entry;
            }
        }
        if (array_key_exists($itemId, $this->previousEntries[$importType])) {
            return $this->previousEntries[$importType][$itemId];
        }
        return null;
    }


    /**
     * Returns the number of unprocessed schedule entries
     *
     * @param ?string $importType Optional data type
     *
     * @return int
     */
    public function countUnprocessedScheduleEntries(?string $importType = null): int
    {
        $queryBuilder = $this->getScheduleQueryBuilder();
        $conditions = [];
        $conditions[] = 'data_processed = 0';
        if (null !== $importType) {
            $conditions[] = $queryBuilder->expr()->eq('import_type', $queryBuilder->createNamedParameter($importType));
        }
        $queryBuilder->count('*')
            ->from(self::TABLE_IMPORT_ROW);
        $queryBuilder->where(...$conditions);
        return (int)$queryBuilder->executeQuery()->fetchOne();
    }


    /**
     * Loads the schedule entries
     * @param string|null $importType Optional data type
     * @param bool $onlyUnprocessed Only load unprocessed items
     * @return array[]
     */
    public function fetchScheduleEntries($importType = null, $onlyUnprocessed = true): array
    {
        $queryBuilder = $this->getScheduleQueryBuilder();
        $conditions = [];
        if ($onlyUnprocessed) {
            $conditions[] = 'data_processed = 0';
        }
        if (null !== $importType) {
            $conditions[] = $queryBuilder->expr()->eq('import_type', $queryBuilder->createNamedParameter($importType));
        }
        $queryBuilder->select('*')
            ->from(self::TABLE_IMPORT_ROW);
        if (!empty($conditions)) {
            $queryBuilder->where(...$conditions);
        }
        $queryBuilder->orderBy('uid', 'ASC');
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Enqueue the item with the processing information
     *
     * @param int $importId
     * @param string $importType
     * @param string $importMethod
     * @param array $data
     * @param int $lastModifiedTstamp
     * @param ?string $dataHash
     * @param ?array<string, mixed> $previousItem
     */
    public function saveQueueItem(
        $importId,
        $importType,
        $importMethod,
        $data,
        $lastModifiedTstamp,
        $dataHash = null,
        $previousItem = null
    ) {
        $now = time();
        $importData = json_encode($data);
        if (null === $dataHash) {
            $dataHash = md5($importData);
        }
        $values = [
            'crdate' => $now,
            'tstamp' => $now,
            'import_uid' => $importId,
            'import_type' => $importType,
            'import_data' => $importData,
            'last_modified_tstamp' => $lastModifiedTstamp,
            'data_processed' => 0,
            'import_method' => $importMethod,
            'data_hash' => $dataHash,
            'target_record_id' => 0,
        ];
        if (!empty($previousItem)) {
            $values['target_record_id'] = $previousItem['target_record_id'];
            $queryBuilder = $this->getScheduleQueryBuilder();
            $queryBuilder->insert(self::TABLE_IMPORT_ROW)->values($values)->executeStatement();
            $deleteQueryBuilder = $this->getScheduleQueryBuilder();
            $deleteQueryBuilder->delete(self::TABLE_IMPORT_ROW)->where('uid = ' . (int) $previousItem['uid'])->executeStatement();
        } else {
            $queryBuilder = $this->getScheduleQueryBuilder();
            $queryBuilder->insert(self::TABLE_IMPORT_ROW)->values($values)->executeStatement();
        }

    }

    /**
     * Mark queue item as finished. Will only be deleted when the next queue item for the same record is created or
     * when the queue items are cleaned up
     *
     * @param int $uid
     * @param int $targetRecordId
     * @param bool $keepImportData
     */
    public function finishScheduleEntryAsImported(int $uid, int $targetRecordId = 0, bool $keepImportData = false): void
    {
        $scheduleConnection = $this->getScheduleConnection();

        $now = time();
        $values = [
            'data_processed' => 1,
            'tstamp' => $now,
            'target_record_id' => $targetRecordId,
        ];
        if (!$keepImportData) {
            $values['import_data'] = json_encode(['finished' => 1]);
        }
        $scheduleConnection->update(self::TABLE_IMPORT_ROW, $values, ['uid' => $uid]);
    }

    /**
     * Clean up old queue items
     * @param string $timeModifier
     */
    public function cleanUp(string $timeModifier = '-1 week'): void
    {
        $deleteQueryBuilder = $this->getScheduleQueryBuilder();
        $deleteQueryBuilder->delete(self::TABLE_IMPORT_ROW)->where('crdate < ' . strtotime($timeModifier))->executeStatement();
    }

    /**
     * Returns a query builder for the scheduler queue table
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getScheduleQueryBuilder()
    {
        return $this->getScheduleConnection()->createQueryBuilder();
    }

    /**
     * Returns the connection for the scheduler queue table
     * @return \TYPO3\CMS\Core\Database\Connection
     */
    private function getScheduleConnection()
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable(self::TABLE_IMPORT_ROW);
    }
}
