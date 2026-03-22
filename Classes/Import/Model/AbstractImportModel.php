<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

/**
 * Import file mapping for ImportDataTransformerInterface
 */
abstract class AbstractImportModel
{

    /**
     * @var string
     */
    protected string $targetTable;

    /**
     * @var int
     */
    protected int $targetRecordId = 0;

    /**
     * @var int The language id
     */
    protected int $languageUid = 0;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     */
    protected ?int $pid = null;

    /**
     * @var int
     */
    protected int $lastUpdated = 0;

    /**
     * Some import types have string identifiers, so we need to separate ID fields for string and int identifiers
     * This ensures the type compatibility of the fields in SQL queries
     * @var string
     */
    protected string $sourceRecordIdentifier;

    /**
     * Constructor method to initialize the object with import data, source type, source record identifier,
     * and an optional data hash.
     *
     * @param string $targetTable The target table for the import source
     * @param int|string $sourceRecordIdentifier The identifier of the source record.
     */
    public function __construct(string $targetTable, string|int $sourceRecordIdentifier)
    {
        $this->targetTable = $targetTable;
        $this->sourceRecordIdentifier = (string)$sourceRecordIdentifier;
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getTargetTable(): string
    {
        return $this->targetTable;
    }

    /**
     * Get source record identifier. Only used if the identifier is a string. Otherwise, getSourceRecordUid is used
     * This is used internally, so we have consistent identifier types
     *
     * @return string
     */
    public function getSourceRecordIdentifier(): string
    {
        return $this->sourceRecordIdentifier;
    }

    /**
     * Get targetRecordId
     *
     * @return int
     */
    public function getTargetRecordId(): int
    {
        return $this->targetRecordId;
    }

    /**
     * Set targetRecordId
     *
     * @param int $targetRecordId
     */
    public function setTargetRecordId(int $targetRecordId): void
    {
        $this->targetRecordId = $targetRecordId;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(?int $pid): void
    {
        $this->pid = $pid;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function setLanguageUid(int $languageUid): void
    {
        $this->languageUid = $languageUid;
    }

    public function getLastUpdated(): int
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(int $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }
}
