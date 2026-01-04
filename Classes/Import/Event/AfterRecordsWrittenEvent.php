<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;

/**
 * Represents an event that occurs when the data transformation process is completed
 */
class AfterRecordsWrittenEvent extends AbstractImportEvent
{

    public function __construct(
        protected AbstractImportOptions     $importOptions,
        protected ImportTargetRecordMapping $targetUidMapping
    )
    {
        parent::__construct($importOptions);
    }

    public function getTargetUidMapping(): ImportTargetRecordMapping
    {
        return $this->targetUidMapping;
    }
}
