<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;

/**
 * Abstract data update event
 */
class AbstractDataUpdateEvent extends AbstractRecordEvent
{

    /**
     * The import record model contains the raw data from the source and the transformed data.
     *
     * @var ImportRecordModel
     */
    protected ImportRecordModel $importRecordModel;

    /**
     * Constructor
     */
    public function __construct(int $uid, string $table, ImportRecordModel $importRecordModel)
    {
        parent::__construct($uid, $table);
        $this->importRecordModel = $importRecordModel;
    }

    public function getImportRecordModel(): ImportRecordModel
    {
        return $this->importRecordModel;
    }

}
