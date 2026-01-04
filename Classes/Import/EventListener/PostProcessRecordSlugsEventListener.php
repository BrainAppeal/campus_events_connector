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
        if ($event->getImportOptions()->isForceUpdate()) {
            foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
                if ($this->slugGenerator->hasSlugField($dataTransformer->getTable())) {
                    $this->slugGenerator->populateSlugs($dataTransformer->getTable());
                }
            }
        } else {
            foreach ($event->getTargetUidMapping()->getAllCreatedOrUpdated() as $targetTable => $recordIdMap) {
                if ($this->slugGenerator->hasSlugField($targetTable)) {
                    $this->slugGenerator->populateSlugs($targetTable, array_keys($recordIdMap));
                }
            }
        }
    }
}
