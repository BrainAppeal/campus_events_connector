<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use BrainAppeal\CampusEventsConnector\Import\PostProcessing\SlugGenerator;

readonly class PostProcessRecordSlugsEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected SlugGenerator $slugGenerator
    ) {}

    public function __invoke(AfterRecordsWrittenEvent $event): void
    {
        $importOptions = $event->getImportOptions();
        $useDataHandlerForSlugUpdates = $importOptions->useDataHandlerForSlugUpdates();
        if ($importOptions->isForceUpdate()) {
            foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
                $targetTable = $dataTransformer->getTable();
                if ($this->slugGenerator->hasSlugField($targetTable)) {
                    $this->slugGenerator->populateSlugs($targetTable, [], $useDataHandlerForSlugUpdates);
                }
            }
        } else {
            foreach ($event->getTargetUidMapping()->getAllCreatedOrUpdated() as $targetTable => $recordIdMap) {
                if ($this->slugGenerator->hasSlugField($targetTable)) {
                    $this->slugGenerator->populateSlugs($targetTable, array_keys($recordIdMap), $useDataHandlerForSlugUpdates);
                }
            }
        }
    }
}
