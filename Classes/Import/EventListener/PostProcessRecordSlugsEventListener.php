<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use BrainAppeal\CampusEventsConnector\Import\PostProcessing\SlugGenerator;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener(
    identifier: 'ce/post-transform/record-slugs'
)]
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
            foreach ($this->dataTransformerFactory->getDataTransformersByContext($event->getContext()) as $dataTransformer) {
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
