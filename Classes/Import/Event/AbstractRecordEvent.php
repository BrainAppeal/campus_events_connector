<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

/**
 * Abstract record event
 */
class AbstractRecordEvent
{
    /**
     * Record uid
     */
    protected int $uid;

    /**
     * Record table
     */
    protected string $table;

    /**
     * Constructor
     */
    public function __construct(int $uid, string $table)
    {
        $this->uid = $uid;
        $this->table = $table;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getTable(): string
    {
        return $this->table;
    }

}
