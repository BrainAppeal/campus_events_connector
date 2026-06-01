<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportContext;

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
        protected ImportContext $context,
    ) {}

    public function getContext(): ImportContext
    {
        return $this->context;
    }

    public function getImportOptions(): AbstractImportOptions
    {
        return $this->context->options;
    }

    public function getImportId(): int
    {
        return $this->context->getImportId();
    }

}
