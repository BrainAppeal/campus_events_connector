<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;

/**
 * Represents an event that occurs when the data transformation process is completed
 */
class AfterRecordsWrittenEvent extends AbstractImportEvent
{
    public function getTargetUidMapping(): ImportTargetRecordMapping
    {
        return $this->context->getTargetRecordMapping();
    }
}
