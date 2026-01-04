<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;

/**
 * Represents an event that occurs when an import process is stopped
 */
class ImportRunCompletedEvent extends AbstractImportEvent
{
    public function __construct(
        protected AbstractImportOptions $importOptions,
        protected ImportEntry $importEntry
    )
    {
        parent::__construct($importOptions);
    }

    public function getImportEntry(): ImportEntry
    {
        return $this->importEntry;
    }

    public function isImportFinished(): bool
    {
        if (empty($this->statistics)) {
            return false;
        }
        // Mark import finished when all rows have been imported
        $remainingRowCount = $this->getTotalRowCount() - $this->getSkippedRowCount();
        return $this->getImportedRowCount() >= $remainingRowCount
            && $this->getFullLoadedRowCount() >= $remainingRowCount;
    }


    /**
     * Get the total rows
     *
     * @return int
     */
    public function getTotalRowCount(): int
    {
        return (int)($this->statistics['total_rows'] ?? 0);
    }

    /**
     * Get the full loaded rows
     *
     * @return int
     */
    public function getFullLoadedRowCount(): int
    {
        return (int)($this->statistics['full_loaded_rows'] ?? 0);
    }

    /**
     * Get the imported rows
     *
     * @return int
     */
    public function getImportedRowCount(): int
    {
        return (int)($this->statistics['imported_rows'] ?? 0);
    }

    /**
     * Get the skipped rows
     *
     * @return int
     */
    public function getSkippedRowCount(): int
    {
        return (int)($this->statistics['skipped_rows'] ?? 0);
    }

}
