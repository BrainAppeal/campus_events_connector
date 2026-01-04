<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when the import configuration is invalid.
 */
class ImportTcaConfigurationException extends \Exception
{
    public function __construct(string $message, int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
