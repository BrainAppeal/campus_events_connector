<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Repository\AbstractImportRowRepository;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;

/**
 * Event listener responsible for excluding tables related to the import from the reference index.
 */
final readonly class ExcludeImportTablesFromReferenceIndexEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
    ) {}

    public function __invoke(IsTableExcludedFromReferenceIndexEvent $event): void
    {
        if (in_array($event->getTable(), [AbstractImportRowRepository::TABLE_IMPORT_ROW, ImportEntryManager::TABLE_IMPORT], true)
            || $this->dataTransformerFactory->hasImportConfigurationForTable($event->getTable())) {
            $event->markAsExcluded();
        }
    }
}
