<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * This exception is thrown when the maximum number of records that can be imported has been reached.
 * This helps prevent overloading the system with too many imports at once.
 */
class ImportLimitReachedException extends \Exception
{
    public function __construct(string $message = 'Import limit has been reached', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
