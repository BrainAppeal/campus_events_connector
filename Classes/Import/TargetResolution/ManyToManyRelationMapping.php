<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\TargetResolution;

final class ManyToManyRelationMapping
{

    /**
     * @var array<string, array<int, array<string|int, ?int>>>
     */
    protected array $manyToManyReferences = [];

    public function __construct(
        protected string $table
    )
    {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Retrieves the collection of many-to-many relationship references.
     *
     * @return array<string, array<int, array<string|int, ?int>>> The array of many-to-many references for the table fields.
     */
    public function getManyToManyReferences(): array
    {
        return $this->manyToManyReferences;
    }

    /**
     * Adds many-to-many relationship references for a specified record.
     *
     * @param int $targetRecordId The ID of the target record in the specified table.
     * @param array<string, int[]> $mmReferences An associative array of references where keys are field names and values are the references to be added.
     * @return void
     */
    public function addManyToManyReference(int $targetRecordId, array $mmReferences): void
    {
        foreach ($mmReferences as $field => $references) {
            $this->manyToManyReferences[$field][$targetRecordId] = $references;
        }
    }
}
