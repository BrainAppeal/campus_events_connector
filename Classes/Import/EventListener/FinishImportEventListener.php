<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\Event\ImportFinishEvent;
use BrainAppeal\CampusEventsConnector\Import\Finisher\CleanupService;
use BrainAppeal\CampusEventsConnector\Import\Finisher\ObsoleteImportedRecordRemover;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use BrainAppeal\CampusEventsConnector\Import\Writer\FileWriter;

readonly class FinishImportEventListener
{
    public function __construct(
        protected CleanupService                $cleanupService,
        protected ObsoleteImportedRecordRemover $obsoleteImportedRecordRemover,
        protected FileWriter                    $fileImport,
    )
    {
    }

    public function __invoke(ImportFinishEvent $event): void
    {
        $importId = $event->getImportId();
        $importOptions = $event->getImportOptions();
        $this->cleanupService->deleteOldImportRows($importOptions->getImportSource(), $importId);
        $this->cleanupService->cleanupOldRecords();
        $this->cleanupService->cleanupOldRecords('-1 month', null, ImportEntryManager::TABLE_IMPORT);
        // If the import entry was interrupted before any row counts were set, we must not clean up old records
        if ($event->getTotalRowCount() > 0) {
            $deletedRowCount = $this->obsoleteImportedRecordRemover->run($importId, $event->getSkippedRowCount());
            $event->setDeletedRowCount($deletedRowCount);
        }

        $fileTargetResourceIdentifier = $importOptions->getFileTargetResourceIdentifier();
        if ($fileTargetResourceIdentifier) {
            $this->fileImport->onImportFinish($fileTargetResourceIdentifier);
        }
    }
}
