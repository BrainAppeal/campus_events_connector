<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when a referenced record cannot be found.
 */
class ReferenceNotFoundException extends \Exception
{
    public function __construct(string $message, int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
