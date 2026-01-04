<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when an import process is already running.
 */
class ImportAlreadyRunningException extends \Exception
{
    /**
     * Constructor for initializing the exception when an import is already running.
     *
     * @param int $importId The identifier of the import process.
     * @param int $code The error code for the exception. Default is 429.
     * @param \Throwable|null $previous The previous throwable used for exception chaining, if any.
     *
     * @return void
     */
    public function __construct(int $importId, int $code = 429, ?\Throwable $previous = null)
    {
        $message = sprintf('Import %d is already running.', $importId);
        parent::__construct($message, $code, $previous);
    }
}
