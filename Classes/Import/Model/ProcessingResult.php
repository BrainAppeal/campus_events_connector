<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;

/**
 * Represents the result of a row processing operation.
 */
class ProcessingResult
{
    /**
     * @var array{processed: int, inserted: int, updated: int}
     */
    private array $recordCounts = [
        'processed' => 0,
        'inserted' => 0,
        'updated' => 0,
    ];

    /**
     * @var ImportRecordModel[]
     */
    private array $allRows;

    /**
     * @var array<string, ImportRecordModel[]>
     */
    private array $modelsToBeAddedGroupedBySourceType = [];

    /**
     * List of models that need date processing. The models are grouped by their target table.
     * @var array<string, array<int, ImportRecordModel>>
     */
    private array $groupedRowsUpdate = [];

    /**
     * List of models that need file processing. The models are grouped by their target table.
     * @var array<string, array<int, ImportRecordModel>>
     */
    private array $processFilesList = [];
    private array $dataTypesProcessed = [];

    private ImportTargetRecordMapping $mapping;

    public function __construct(array $rows, ImportTargetRecordMapping $mapping)
    {
        $this->allRows = $rows;
        $this->mapping = $mapping;
    }

    public function getMapping(): ImportTargetRecordMapping
    {
        return $this->mapping;
    }

    /**
     * Returns all rows that were processed
     *
     * @return ImportRecordModel[] The array containing all rows.
     */
    public function getAllRows(): array
    {
        return $this->allRows;
    }

    /**
     * Returns the number of rows that were processed, inserted, or updated.
     * @return array{processed: int, inserted: int, updated: int}
     */
    public function getRecordCounts(): array
    {
        return $this->recordCounts;
    }

    public function incrementProcessedRowCount(int $count = 1): void
    {
        $this->recordCounts['processed'] += $count;
    }

    public function incrementRecordCount(int $count, bool $isInsertedRecords): void
    {
        $this->recordCounts[$isInsertedRecords ? 'inserted' : 'updated'] += $count;
    }

    public function incrementInsertedRowCount(int $count = 1): void
    {
        $this->recordCounts['inserted'] += $count;
    }

    public function incrementUpdatedRowCount(int $count = 1): void
    {
        $this->recordCounts['updated'] += $count;
    }

    /**
     * Retrieves the models that need to be added, grouped by their source type.
     *
     * @return array<string, ImportRecordModel[]>
     */
    public function getModelsToBeAddedGroupedBySourceType(): array
    {
        return $this->modelsToBeAddedGroupedBySourceType;
    }

    /**
     * Adds a new import record model to the group associated with the given source type.
     *
     * @param string $sourceType The source type to associate the model with.
     * @param ImportRecordModel $model The import record model to be added.
     */
    public function addNewGroupModelForType(string $sourceType, ImportRecordModel $model): void
    {
        $this->modelsToBeAddedGroupedBySourceType[$sourceType][] = $model;
    }

    public function getGroupedRowsUpdate(): array
    {
        return $this->groupedRowsUpdate;
    }

    public function setGroupedRowsUpdate(array $groupedRowsUpdate): void
    {
        $this->groupedRowsUpdate = $groupedRowsUpdate;
    }

    public function getProcessFilesList(): array
    {
        return $this->processFilesList;
    }

    public function setProcessFilesList(array $processFilesList): void
    {
        $this->processFilesList = $processFilesList;
    }

    public function addDataTypeProcessed(string $dataType): void
    {
        if (!in_array($dataType, $this->dataTypesProcessed, true)) {
            $this->dataTypesProcessed[] = $dataType;
        }
    }

    public function getDataTypesProcessed(): array
    {
        return $this->dataTypesProcessed;
    }
}
