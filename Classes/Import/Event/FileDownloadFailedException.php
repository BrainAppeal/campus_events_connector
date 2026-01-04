<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

/**
 * Exception thrown when a file download operation fails.
 *
 * This exception allows specifying whether the existing file should
 * be preserved or deleted upon failure. It extends the standard
 * RuntimeException to provide additional context related to file download errors.
 */
class FileDownloadFailedException extends \RuntimeException
{
    public const NOT_FOUND = 404;

    /**
     * Indicate if existing file should be kept or deleted
     *
     * @var bool
     */
    private bool $keepExistingFile;

    /**
     * Constructor for the class.
     *
     * @param string $message A descriptive error message.
     * @param bool $keepExistingFile Optional flag to indicate whether to keep the existing file. Default is false.
     * @param int $code Optional error code. Default is self::NOT_FOUND.
     * @param \Throwable|null $previous Optional previous throwable for exception chaining.
     *
     * @return void
     */
    public function __construct(string $message, bool $keepExistingFile = false, int $code = self::NOT_FOUND, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->keepExistingFile = $keepExistingFile;
    }

    public function getKeepExistingFile(): bool
    {
        return $this->keepExistingFile;
    }


}
