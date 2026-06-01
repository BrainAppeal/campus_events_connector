<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;
use BrainAppeal\CampusEventsConnector\Import\Model\ProcessingResult;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportContext;

/**
 * Represents an event that occurs during the processing of import rows.
 * This class provides access to the date of the processing operation.
 */
abstract class AbstractProcessEvent extends AbstractImportEvent
{
    public function __construct(
        protected ImportContext $context,
        protected ProcessingResult $processingResult
    ) {
        parent::__construct($context);
    }

    public function getProcessingResult(): ProcessingResult
    {
        return $this->processingResult;
    }

    public function getImportEntry(): ImportEntry
    {
        return $this->context->getImportEntry();
    }

}
