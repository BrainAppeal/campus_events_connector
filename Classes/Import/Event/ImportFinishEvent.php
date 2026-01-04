<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;

/**
 * Represents an event that occurs when an import is finished
 */
class ImportFinishEvent extends AbstractImportEvent
{
    protected int $deletedRowCount = 0;

    public function __construct(
        protected AbstractImportOptions $importOptions,
        private readonly ImportEntry $importEntry
    )
    {
        parent::__construct($importOptions);
    }

    public function getImportEntry(): ImportEntry
    {
        return $this->importEntry;
    }

    public function getTotalRowCount(): int
    {
        return $this->importEntry->getTotalRowCount();
    }

    public function getSkippedRowCount(): int
    {
        return $this->importEntry->getSkippedRowCount();
    }

    public function getDeletedRowCount(): int
    {
        return $this->deletedRowCount;
    }

    public function setDeletedRowCount(int $deletedRowCount): void
    {
        $this->deletedRowCount = $deletedRowCount;
    }

}
