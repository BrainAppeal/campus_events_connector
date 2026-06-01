<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\Event\ImportFinishEvent;
use BrainAppeal\CampusEventsConnector\Import\Finisher\CleanupService;
use BrainAppeal\CampusEventsConnector\Import\Finisher\ObsoleteImportedRecordRemover;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use BrainAppeal\CampusEventsConnector\Import\Writer\FileWriter;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener(
    identifier: 'ce/import/finish'
)]
readonly class FinishImportEventListener
{
    public function __construct(
        protected CleanupService $cleanupService,
        protected ObsoleteImportedRecordRemover $obsoleteImportedRecordRemover,
        protected FileWriter $fileImport,
    ) {}

    public function __invoke(ImportFinishEvent $event): void
    {
        $context = $event->getContext();
        $importId = $context->getImportId();
        $this->cleanupService->deleteOldImportRows($context->getImportSource(), $importId);
        $this->cleanupService->cleanupOldRecords();
        $this->cleanupService->cleanupOldRecords('-1 month', null, ImportEntryManager::TABLE_IMPORT);
        // If the import entry was interrupted before any row counts were set, we must not clean up old records
        if ($event->getTotalRowCount() > 0) {
            $deletedRowCount = $this->obsoleteImportedRecordRemover->run($context, $event->getSkippedRowCount());
            $event->setDeletedRowCount($deletedRowCount);
        }

        $fileTargetResourceIdentifier = $context->options->getFileTargetResourceIdentifier();
        if ($fileTargetResourceIdentifier) {
            $this->fileImport->onImportFinish($fileTargetResourceIdentifier);
        }
    }
}
