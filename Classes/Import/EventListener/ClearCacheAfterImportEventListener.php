<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use BrainAppeal\CampusEventsConnector\Import\Utility\Cache;

/**
 * This class listens for the AfterRecordsWrittenEvent and
 * performs actions to clear related cache tags and record caches.
 *
 * The event contains information such as processed record IDs grouped by
 * source type and cache tag details. Based on this information, the class
 * clears both the default cache tag associated with the transformation process
 * and specific record caches for the processed data.
 */
readonly class ClearCacheAfterImportEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory
    )
    {
    }

    /**
     * Handles the AfterRecordsWrittenEvent to clear cache tags
     * and record caches for the processed data.
     *
     * @param AfterRecordsWrittenEvent $event The event instance containing
     *                                                     processed record IDs grouped by source type
     *                                                     and cache tag information.
     *
     * @return void
     */
    public function __invoke(AfterRecordsWrittenEvent $event): void
    {
        $processRecordIdsGroupedByTargetTable = $event->getTargetUidMapping()->getAllCreatedOrUpdated();
        if (empty($processRecordIdsGroupedByTargetTable)) {
            return;
        }
        $tagPrefix = $event->getImportOptions()->getCachePrefix();
        Cache::clearDefaultCacheTag($tagPrefix);
        foreach ($processRecordIdsGroupedByTargetTable as $targetTable => $recordIdMap) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            Cache::clearRecordCaches($dataTransformer->getTable(), array_keys($recordIdMap));
        }
    }
}
