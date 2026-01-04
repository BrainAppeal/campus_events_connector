<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * This exception is thrown when the maximum number of allowed API calls has been reached.
 * This helps prevent exceeding API rate limits.
 */
class ApiLimitReachedException extends ImportLimitReachedException
{
    public function __construct(string $message = 'API call limit has been reached', int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
