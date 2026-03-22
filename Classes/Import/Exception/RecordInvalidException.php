<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when a record is invalid and cannot be imported.
 */
class RecordInvalidException extends \Exception
{
    public function __construct(string $message, int $code = 430, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
