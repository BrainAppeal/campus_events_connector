<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Workflow;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;

class ImportContext
{
    private ?ImportEntry $importEntry = null;
    private ImportTargetRecordMapping $targetRecordMapping;

    /**
     * @var array<string, string[]>
     */
    private array $errorsByTable = [];

    private bool $isStartOfNewImport = false;

    public function __construct(
        public readonly AbstractImportOptions $options
    ) {
        $this->targetRecordMapping = new ImportTargetRecordMapping();
    }

    public function getImportSource(): string
    {
        return $this->options->getImportSource();
    }

    public function getTargetRecordMapping(): ImportTargetRecordMapping
    {
        return $this->targetRecordMapping;
    }

    public function hasImportEntry(): bool
    {
        return $this->importEntry !== null;
    }

    public function setImportEntry(ImportEntry $importEntry): void
    {
        $this->importEntry = $importEntry;
    }

    public function getImportId(): int
    {
        return $this->getImportEntry()->getUid();
    }

    public function isStartOfNewImport(): bool
    {
        return $this->isStartOfNewImport;
    }

    public function setIsStartOfNewImport(bool $isStartOfNewImport): void
    {
        $this->isStartOfNewImport = $isStartOfNewImport;
    }

    public function getImportEntry(): ImportEntry
    {
        if ($this->importEntry === null) {
            throw new \RuntimeException('Import entry not set');
        }
        return $this->importEntry;
    }

    /**
     * Retrieves the errors grouped by the corresponding database tables.
     *
     * @return array<string, string[]> An associative array where the keys represent table names
     * and the values are arrays containing error details for each table. Returns an empty array if no errors are recorded.
     */
    public function getErrorsByTable(): array
    {
        return $this->errorsByTable;
    }

    public function addTableError(string $table, string $message): void
    {
        $this->errorsByTable[$table][] = $message;
    }

}
