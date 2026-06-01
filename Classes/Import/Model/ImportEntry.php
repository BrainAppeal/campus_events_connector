<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

class ImportEntry
{
    /**
     * @var int<1, max>|null The uid of the record. The uid is only unique in the context of the database table.
     */
    protected ?int $uid = null;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     */
    protected ?int $pid = null;

    /**
     * @var int
     */
    protected int $crdate = 0;

    /**
     * @var int
     */
    protected int $tstamp = 0;

    /**
     * @var bool
     */
    protected bool $hidden = false;

    /**
     * @var bool
     */
    protected bool $running = false;

    /**
     * @var \DateTime|null
     */
    protected ?\DateTime $importStart = null;

    /**
     * @var \DateTime|null
     */
    protected ?\DateTime $importEnd = null;

    /**
     * @var \DateTime|null
     */
    protected ?\DateTime $importSourceModifiedAt = null;

    /**
     * @var string
     */
    protected string $importSource = '';

    /**
     * @var int
     */
    protected int $importedRowCount = 0;

    /**
     * @var int
     */
    protected int $fullLoadedRowCount = 0;

    /**
     * @var int
     */
    protected int $totalRowCount = 0;

    /**
     * @var int
     */
    protected int $skippedRowCount = 0;

    /**
     * @var int
     */
    protected int $importLimit = 500;

    /**
     * @var bool
     */
    protected bool $firstImportDone = false;
    public function __construct(array $record = [])
    {
        if (!empty($record)) {
            $this->setFromArray($record);
        } else {
            $this->setTstamp(time());
            $this->setCrdate(time());
        }
    }

    public function setFromArray(array $record): void
    {
        if (isset($record['uid'])) {
            $this->uid = (int)$record['uid'];
        }
        if (isset($record['pid'])) {
            $this->pid = (int)$record['pid'];
        }
        if (isset($record['tstamp'])) {
            $this->setTstamp((int)$record['tstamp']);
        }
        if (isset($record['crdate'])) {
            $this->setCrdate((int)$record['crdate']);
        }
        if (isset($record['hidden'])) {
            $this->setHidden((bool)$record['hidden']);
        }
        if (isset($record['running'])) {
            $this->setRunning((bool)$record['running']);
        }
        if (isset($record['import_start'])) {
            $this->setImportStart((new \DateTime())->setTimestamp((int)$record['import_start']));
        }
        if (isset($record['import_end'])) {
            $this->setImportEnd((new \DateTime())->setTimestamp((int)$record['import_end']));
        }
        if (isset($record['import_source'])) {
            $this->setImportSource((string)$record['import_source']);
        }
        if (isset($record['total_row_count'])) {
            $this->setTotalRowCount((int)$record['total_row_count']);
        }
        if (isset($record['full_loaded_row_count'])) {
            $this->setFullLoadedRowCount((int)$record['full_loaded_row_count']);
        }
        if (isset($record['imported_row_count'])) {
            $this->setImportedRowCount((int)$record['imported_row_count']);
        }
        if (isset($record['skipped_row_count'])) {
            $this->setSkippedRowCount((int)$record['skipped_row_count']);
        }
        if (isset($record['import_limit'])) {
            $this->setImportLimit((int)$record['import_limit']);
        }
        if (isset($record['import_source_modified_at'])) {
            $this->setImportSourceModifiedAt((new \DateTime())->setTimestamp((int)$record['import_source_modified_at']));
        }
        if (isset($record['first_import_done'])) {
            $this->setFirstImportDone((bool)$record['first_import_done']);
        }
    }

    public function toArray(): array
    {
        return [
            'uid' => $this->getUid(),
            'pid' => $this->getPid(),
            'tstamp' => $this->getTstamp(),
            'crdate' => $this->getCrdate(),
            'hidden' => $this->getHidden() ? 1 : 0,
            'running' => $this->isRunning() ? 1 : 0,
            'import_start' => $this->getImportStart()?->getTimestamp() ?? 0,
            'import_end' => $this->getImportEnd()?->getTimestamp() ?? 0,
            'import_source' => $this->getImportSource(),
            'total_row_count' => $this->getTotalRowCount(),
            'full_loaded_row_count' => $this->getFullLoadedRowCount(),
            'imported_row_count' => $this->getImportedRowCount(),
            'skipped_row_count' => $this->getSkippedRowCount(),
            'import_limit' => $this->getImportLimit(),
            'import_source_modified_at' => $this->getImportSourceModifiedAt()?->getTimestamp() ?? 0,
            'first_import_done' => $this->isFirstImportDone() ? 1 : 0,
        ];
    }

    /**
     * @param int<0, max> $uid
     */
    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * @return int<1, max>|null
     */
    public function getUid(): ?int
    {
        if ($this->uid !== null) {
            return (int)$this->uid;
        }
        return null;
    }

    /**
     * @param int<0, max> $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return int<0, max>|null
     */
    public function getPid(): ?int
    {
        if ($this->pid === null) {
            return null;
        }
        return (int)$this->pid;
    }

    /**
     * Get Tstamp
     *
     * @return int
     */
    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    /**
     * Set tstamp
     *
     * @param int $tstamp tstamp
     */
    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function getCrdate(): int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * Get Hidden
     *
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set Hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        // set the run time limit to 1 hour since the last change; since the import entry is updated after every 20 import rows
        // this should always be the case if the process is running without errors
        // We need this in case there is an import error and the process is stopped unexpectedly
        if ($this->running && $this->getTstamp() < time() - 3600) {
            $this->running = false;
        }
        return $this->running;
    }

    public function setRunning(bool $running): void
    {
        $this->running = $running;
    }

    /**
     * @return \DateTime|null
     */
    public function getImportStart(): ?\DateTime
    {
        return $this->importStart;
    }

    public function setImportStart(?\DateTime $importStart): void
    {
        $this->importStart = $importStart;
    }

    /**
     * @return \DateTime|null
     */
    public function getImportEnd(): ?\DateTime
    {
        return $this->importEnd;
    }

    public function setImportEnd(?\DateTime $importEnd): void
    {
        $this->importEnd = $importEnd;
    }

    /**
     * @return \DateTime|null
     */
    public function getImportSourceModifiedAt(): ?\DateTime
    {
        return $this->importSourceModifiedAt;
    }

    public function setImportSourceModifiedAt(?\DateTime $importSourceModifiedAt): void
    {
        $this->importSourceModifiedAt = $importSourceModifiedAt;
    }

    /**
     * @return string
     */
    public function getImportSource(): string
    {
        return $this->importSource;
    }

    public function setImportSource(string $importSource): void
    {
        $this->importSource = $importSource;
    }

    /**
     * @return int
     */
    public function getImportedRowCount(): int
    {
        return $this->importedRowCount;
    }

    public function setImportedRowCount(int $importedRowCount): void
    {
        $this->importedRowCount = $importedRowCount;
    }

    /**
     * @return int
     */
    public function getFullLoadedRowCount(): int
    {
        return $this->fullLoadedRowCount;
    }

    public function setFullLoadedRowCount(int $fullLoadedRowCount): void
    {
        $this->fullLoadedRowCount = $fullLoadedRowCount;
    }

    /**
     * @return int
     */
    public function getTotalRowCount(): int
    {
        return $this->totalRowCount;
    }

    public function setTotalRowCount(int $totalRowCount): void
    {
        $this->totalRowCount = $totalRowCount;
    }

    public function getSkippedRowCount(): int
    {
        return $this->skippedRowCount;
    }

    public function setSkippedRowCount(int $skippedRowCount): void
    {
        $this->skippedRowCount = $skippedRowCount;
    }

    /**
     * @return int
     */
    public function getImportLimit(): int
    {
        return $this->importLimit;
    }

    public function setImportLimit(int $importLimit): void
    {
        $this->importLimit = $importLimit;
    }

    /**
     * @return bool
     */
    public function isFirstImportDone(): bool
    {
        return $this->firstImportDone;
    }

    public function setFirstImportDone(bool $firstImportDone): void
    {
        $this->firstImportDone = $firstImportDone;
    }

    /**
     * Mark the import entry as finished
     */
    public function markAsFinished(): void
    {
        $this->setImportEnd(date_create());
        $this->setRunning(false);
        $this->setHidden(true);
    }

    public function isImportFinished(): bool
    {
        // Mark import finished when all rows have been imported
        $remainingRowCount = $this->getTotalRowCount() - $this->getSkippedRowCount();
        return $this->getImportedRowCount() >= $remainingRowCount
            && $this->getFullLoadedRowCount() >= $remainingRowCount;
    }
}
