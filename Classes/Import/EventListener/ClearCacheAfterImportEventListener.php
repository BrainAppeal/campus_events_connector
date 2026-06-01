<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use BrainAppeal\CampusEventsConnector\Import\Utility\Cache;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * This class listens for the AfterRecordsWrittenEvent and
 * performs actions to clear related cache tags and record caches.
 *
 * The event contains information such as processed record IDs grouped by
 * source type and cache tag details. Based on this information, the class
 * clears both the default cache tag associated with the transformation process
 * and specific record caches for the processed data.
 */
#[AsEventListener(
    identifier: 'ce/post-transform/clear-cache'
)]
readonly class ClearCacheAfterImportEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory
    ) {}

    /**
     * Handles the AfterRecordsWrittenEvent to clear cache tags
     * and record caches for the processed data.
     *
     * @param AfterRecordsWrittenEvent $event The event instance containing
     *                                                     processed record IDs grouped by source type
     *                                                     and cache tag information.
     */
    public function __invoke(AfterRecordsWrittenEvent $event): void
    {
        $processRecordIdsGroupedByTargetTable = $event->getTargetUidMapping()->getAllCreatedOrUpdated();
        if (empty($processRecordIdsGroupedByTargetTable)) {
            return;
        }
        $context = $event->getContext();
        $tagPrefix = $context->options->getCachePrefix();
        Cache::clearDefaultCacheTag($tagPrefix);
        foreach ($processRecordIdsGroupedByTargetTable as $targetTable => $recordIdMap) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            Cache::clearRecordCaches($dataTransformer->getTable(), array_keys($recordIdMap));
        }
    }
}
