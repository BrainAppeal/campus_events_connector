<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;

/**
 * Represents an abstract event for an import process.
 *
 * This class provides access to import options and the associated import ID.
 *
 * Methods:
 * - getImportOptions: Retrieves the options associated with the import.
 * - getImportId: Retrieves the unique identifier for the import process.
 */
class AbstractImportEvent
{
    public function __construct(
        protected AbstractImportOptions $importOptions,
    ) {}

    public function getImportOptions(): AbstractImportOptions
    {
        return $this->importOptions;
    }

    public function getImportId(): int
    {
        return $this->importOptions->getImportId();
    }

}
