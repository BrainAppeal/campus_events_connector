<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Exception;

/**
 * Exception thrown when data validation fails.
 */
class StopImportException extends \RuntimeException
{
    public const CODE_STOP_IMPORT_UNKNOWN = 0;
    public const CODE_STOP_IMPORT_NO_DATA = 1;

    public function __construct(string $message = 'Import stopped', int $code = self::CODE_STOP_IMPORT_NO_DATA)
    {
        parent::__construct($message, $code);
    }

    public function isErrorCode(): bool
    {
        return $this->code !== self::CODE_STOP_IMPORT_NO_DATA;
    }
}
