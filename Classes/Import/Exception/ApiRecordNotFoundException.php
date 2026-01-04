<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when a specific record is not found during an API request.
 *
 * This exception indicates that the requested record does not exist
 * or cannot be located in the context of the API operation.
 */
class ApiRecordNotFoundException extends \Exception
{
    public function __construct(string $message = 'The API returned a not found error for the requested record', int $code = 431, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
