<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Workflow;

use Doctrine\DBAL\Exception;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\PostProcessBatchEvent;
use BrainAppeal\CampusEventsConnector\Import\ImportOptionsFactory;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ProcessingResult;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportRecordReader;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ReferenceResolver;
use BrainAppeal\CampusEventsConnector\Import\Writer\ReferenceWriter;
use BrainAppeal\CampusEventsConnector\Import\Writer\TargetRecordWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImportRecordWorkflow
 *
 * Manages the workflow involved in processing and importing records.
 * It handles tasks such as transforming collected data, processing rows in batches,
 * and dispatching events for specific actions during the import process.
 */
readonly class ImportRecordWorkflow
{
    private ImportTargetRecordMapping $mapping;

    public function __construct(
        protected ImportEntryManager       $importEntryManager,
        protected DataTransformerFactory   $dataTransformerFactory,
        protected ImportOptionsFactory     $importOptionsFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected TargetRecordWriter       $targetRecordPersister,
        protected LoggerInterface          $logger,
        protected ReferenceResolver        $referenceResolver,
        protected ReferenceWriter          $referenceWriter
    )
    {

        $this->mapping = $referenceResolver->getMapping();
    }

    protected function getImportOptions(): AbstractImportOptions
    {
        return $this->importOptionsFactory->get();
    }

    /**
     * Transforms the collected date for the current import operation.
     * If the current import has a total limit of processed tows set, the given collected row count is used to lower
     * the limit of rows to be transformed. This prevents the import process from running too long
     *
     * @param ImportEntry $importEntry
     * @param ?int $maxRowsToProcess The maximum number of rows to transform. If not set, all available rows will be processed
     * @return int The total number of rows processed during this execution.
     * @throws Exception
     */
    public function transformCollectedData(ImportEntry $importEntry, ?int $maxRowsToProcess = null): int
    {
        $importId = $importEntry->getUid();
        $importRecordWriter = $this->importEntryManager->getImportRecordWriter();
        // 1. Set the target records for all existing records
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            $importRecordWriter->updateTargetRecordIdsForType($importId, $dataTransformer->getImportConfiguration());
            $this->referenceResolver->refreshTargetRecordIdMapping($importId, $dataTransformer->getImportConfiguration());
        }
        $totalProcessedRowCount = 0;
        $sumRows = $this->importEntryManager->getImportRecordReader()->countRowsForProcessingByLanguage($importId);
        if (!$maxRowsToProcess) {
            $limit = 0;
            foreach ($sumRows as $sumRow) {
                $limit += $sumRow['sum_data_not_processed'] + $sumRow['sum_files_not_processed'];
            }
        } else {
            $limit = $maxRowsToProcess;
        }
        $memoryBefore = memory_get_usage();
        $startTime = microtime(true);
        foreach ($sumRows as $sumRow) {
            $language = (int)$sumRow['sys_language_uid'];
            $countUnmappedRows = $sumRow['sum_unmapped'];
            $countMappedRows = $sumRow['sum_mapped'];
            $countFilesNotProcessed = $sumRow['sum_files_not_processed'];
            // Process unmapped rows first
            if ($countUnmappedRows > 0) {
                $totalProcessedRowCount += $this->processImportRowsInBatches($importEntry, $language, $limit, true);
                // Set the target records for all existing records
                foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
                    $importRecordWriter->updateTargetRecordIdsForType($importId, $dataTransformer->getImportConfiguration());
                    $this->referenceResolver->refreshTargetRecordIdMapping($importId, $dataTransformer->getImportConfiguration());
                }
            }
            // Then process mapped rows
            if ($limit > $totalProcessedRowCount && ($countMappedRows > 0 || $countFilesNotProcessed > 0)) {
                $totalProcessedRowCount += $this->processImportRowsInBatches($importEntry, $language, $limit, false);
            }
            if ($limit <= $totalProcessedRowCount) {
                break;
            }
        }
        if ($this->getImportOptions()->isEnableStatistics()) {
            $memoryAfter = memory_get_usage();
            $endTime = microtime(true);
            $message = sprintf(
                'Import transformation finished: %d rows processed. Memory: %s (diff: %s). Time: %.4fs.',
                $totalProcessedRowCount,
                GeneralUtility::formatSize($memoryAfter),
                GeneralUtility::formatSize($memoryAfter - $memoryBefore),
                $endTime - $startTime
            );
            $this->logger->info($message);
        }
        $this->postProcessAfterTransformationCompleted();
        return $totalProcessedRowCount;
    }

    /**
     * Processes import rows in batches for a given import entry, up to the specified limit.
     *
     * This method fetches a limited number of rows in each iteration, processes them,
     * and updates their processing state. The process continues until either the limit
     * is reached or there are no more rows left to process.
     *
     * @param ImportEntry $importEntry
     * @param int $language The language identifier of the rows to process.
     * @param int $limit The maximum number of rows to process.
     * @param bool $unmappedRowsOnly Indicates whether to process only unmapped rows or all rows.
     * @return int The total number of rows processed.
     * @throws Exception
     */
    protected function processImportRowsInBatches(ImportEntry $importEntry, int $language, int $limit, bool $unmappedRowsOnly): int
    {
        $importRowType = $unmappedRowsOnly ? ImportRecordReader::IMPORT_ROW_TYPE_UNMAPPED : ImportRecordReader::IMPORT_ROW_TYPE_MAPPED;
        $totalProcessedRowCount = 0;
        // If the limit is 0, there is nothing to process
        $importOptions = $this->getImportOptions();
        $importId = $importOptions->getImportId();
        $maxRowsToProcessPerIteration = $importOptions->getMaxRowsToProcessPerIteration();
        $enableStatistics = $importOptions->isEnableStatistics();
        $importRecordReader = $this->importEntryManager->getImportRecordReader();
        $importRecordWriter = $this->importEntryManager->getImportRecordWriter();
        while ($totalProcessedRowCount < $limit) {
            $rowsToFetch = min($maxRowsToProcessPerIteration, $limit - $totalProcessedRowCount);

            if ($rowsToFetch <= 0) {
                // Should not happen if $limit > 0 and the loop condition is correct,
                // but as a safety measure
                break;
            }

            $rows = $importRecordReader->findImportRows($importId, $rowsToFetch, $importRowType, $language);
            if (empty($rows)) {
                // No more rows found to process
                break;
            }

            $memoryBefore = 0;
            $startTime = 0;
            if ($enableStatistics) {
                $memoryBefore = memory_get_usage();
                $startTime = microtime(true);
            }
            // 1. Initialize the processing result (group by source type and status)
            $processingResult = $this->initializeProcessingResult($rows);
            // 2. Map dependencies for all existing target records. ID's of newly added rows will be added to the mapping later.
            $this->initializeDependencyMapping($processingResult);
            // 3. Process new rows
            $this->processNewRows($processingResult);
            // 4. Now update the existing rows with changes
            $this->processUpdatedRows($processingResult);
            // 5. Process records with unresolved references
            $this->referenceResolver->logUnresolvedReferences($processingResult);
            // 6. Dispatch events post-processing
            $event = new PostProcessBatchEvent($processingResult, $importOptions, $importEntry);
            $this->eventDispatcher->dispatch($event);
            // Update the processed import rows according to their current processing state
            $importRecordWriter->updateProcessingStatesForImportRows($importId, $processingResult->getAllRows());
            $this->importEntryManager->updateActiveEntry($event->getImportEntry());
            $recordCounts = $processingResult->getRecordCounts();
            $batchProcessedRowCount = $recordCounts['processed'];
            $totalProcessedRowCount += $batchProcessedRowCount;

            if ($enableStatistics) {
                $memoryAfter = memory_get_usage();
                $endTime = microtime(true);
                $message = sprintf(
                    'Import batch processed: %d rows (total: %d / %d; %s created, %d updated). Memory: %s (diff: %s). Time: %.4fs.',
                    $batchProcessedRowCount,
                    $totalProcessedRowCount,
                    $limit > 0 ? $limit : 'unlimited',
                    $recordCounts['inserted'],
                    $recordCounts['updated'],
                    GeneralUtility::formatSize($memoryAfter),
                    GeneralUtility::formatSize($memoryAfter - $memoryBefore),
                    $endTime - $startTime
                );
                $this->logger->info($message);
            }
        }
        return $totalProcessedRowCount;
    }

    /**
     * Retrieve the mapping configuration for the import target records.
     *
     * @return ImportTargetRecordMapping The mapping associated with the import target records
     */
    public function getMapping(): ImportTargetRecordMapping
    {
        return $this->mapping;
    }

    /**
     * Initializes the ProcessingResult object based on the provided rows. The given rows are grouped by source type and processed status.
     *
     * @param ImportRecordModel[] $rows An array of models containing data to be processed. Each model is expected to provide methods
     *                    for accessing source type, data processing status, target record ID, and file processing status.
     * @return ProcessingResult The populated ProcessingResult object containing grouped rows and process files list.
     */
    protected function initializeProcessingResult(array $rows): ProcessingResult
    {
        $processingResult = new ProcessingResult($rows, $this->mapping);
        $groupedRowsUpdate = $processingResult->getGroupedRowsUpdate();
        $processFilesList = $processingResult->getProcessFilesList();

        foreach ($rows as $model) {
            $targetTable = $model->getTargetTable();
            if (!$model->getDataProcessed()) {
                $processingResult->addDataTypeProcessed($targetTable);
                if ($targetRecordId = $model->getTargetRecordId()) {
                    $groupedRowsUpdate[$targetTable][$targetRecordId] = $model;
                } else {
                    $processingResult->addNewGroupModelForType($targetTable, $model);
                }
                $model->setDataProcessed(true);
            } elseif (!$model->getFilesProcessed() && $targetRecordId = $model->getTargetRecordId()) {
                // This ensures that files are processed after the normal data processing is completed
                $processFilesList[$targetTable][$targetRecordId] = $model;
                $model->setFilesProcessed(true);
            }
        }
        $processingResult->setGroupedRowsUpdate($groupedRowsUpdate);
        $processingResult->setProcessFilesList($processFilesList);
        return $processingResult;
    }

    /**
     * Initializes the dependency mapping for processed data types using the provided ProcessingResult object.
     * This involves determining table dependencies and mapping them using existing data transformers.
     *
     * @param ProcessingResult $processingResult The result object containing the list of data types that have been processed.
     *                                            Each data type is used to fetch corresponding dependencies and map them.
     * @return void
     */
    protected function initializeDependencyMapping(ProcessingResult $processingResult): void
    {
        foreach ($processingResult->getDataTypesProcessed() as $targetTable) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            $dependenciesToOtherTables = $dataTransformer->getImportConfiguration()->getDependenciesToOtherTables();
            if (!empty($dependenciesToOtherTables)) {
                $this->referenceResolver->mapDependencies($dependenciesToOtherTables);
            }
        }
    }

    /**
     * Processes new rows to be added based on their dependencies and source types.
     * The method processes rows in two stages:
     * 1. Rows without dependencies to other import tables are transformed and processed first.
     * 2. Rows with dependencies to other import tables are processed after the dependencies have been resolved.
     *
     * @param ProcessingResult $processingResult The ProcessingResult instance containing grouped rows and tracking of processed row counts.
     *                                           Each group of rows is organized by source type and expected to be processed accordingly.
     * @return void
     */
    protected function processNewRows(ProcessingResult $processingResult): void
    {
        /*
         * 2.1. Transform the collected data for new rows. In the first iteration only data types without
         * dependencies to other import tables are processed.
         * These data types also get a higher priority in the import table, which ensures that these rows will be
         * created first
         * The goal is that we can resolve all dependencies before processing the rest of the data.
         */
        $groupedRowsNew = $processingResult->getModelsToBeAddedGroupedBySourceType();
        foreach ($groupedRowsNew as $targetTable => $importModels) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            $importConfiguration = $dataTransformer->getImportConfiguration();
            if (empty($importConfiguration->getDependenciesToOtherTables())) {
                $this->processImportModels($processingResult, $targetTable, $importModels, true);
            }
        }
        /*
         * 2.2. In the second iteration only data types with dependencies to other import tables are processed.
         * When we reach this point, we can assume that all dependencies to other tables have been resolved.
         * Only dependencies to the same table are not resolved yet.
         */
        foreach ($groupedRowsNew as $targetTable => $importModels) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            $importConfiguration = $dataTransformer->getImportConfiguration();
            $dependenciesToOtherTables = $importConfiguration->getDependenciesToOtherTables();
            if (!empty($dependenciesToOtherTables)) {
                $this->processImportModels($processingResult, $targetTable, $importModels, true);
            }
        }
        // Update the target record IDs for newly added rows; this is only needed if the import rows are loaded again
        $importOptions = $this->getImportOptions();
        $importId = $importOptions->getImportId();
        $targetTables = array_keys($groupedRowsNew);
        $importRecordWriter = $this->importEntryManager->getImportRecordWriter();
        foreach ($targetTables as $targetTable) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            $importRecordWriter->updateTargetRecordIdsForType($importId, $dataTransformer->getImportConfiguration());
        }
    }

    /**
     * Processes updated rows based on the data available in the given ProcessingResult object. This involves transforming
     * the data and persisting updated rows while keeping track of the processed row count.
     *
     * @param ProcessingResult $processingResult The ProcessingResult object containing grouped rows organized by source type,
     *                                           which are to be processed and counted as updated.
     * @return void
     */
    protected function processUpdatedRows(ProcessingResult $processingResult): void
    {
        $groupedRowsUpdate = $processingResult->getGroupedRowsUpdate();
        foreach ($groupedRowsUpdate as $targetTable => $importModels) {
            $this->processImportModels($processingResult, $targetTable, $importModels, false);
        }
    }

    /**
     * Processes the given import models
     *
     * @param ImportRecordModel[] $importModels
     * @return void
     */
    protected function processImportModels(ProcessingResult $processingResult, string $table, array $importModels, bool $doCreateRecords): void
    {
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($table);
        $processingResult->incrementProcessedRowCount(count($importModels));
        $this->runDataTransformation($processingResult, $dataTransformer, $importModels);
        $writtenRecordCount = $this->targetRecordPersister->writeRecords($dataTransformer->getImportConfiguration(), $importModels, $this->referenceResolver, $doCreateRecords);
        $processingResult->incrementRecordCount($writtenRecordCount, $doCreateRecords);
    }

    /**
     * Executes the data transformation process for the provided grouped rows. Each group of models is processed based on its source type,
     * converting raw import data into a normalized format and enriching it with additional metadata.
     *
     * @param ImportDataTransformerInterface $dataTransformer
     * @param ImportRecordModel[] $importModels
     * @return void
     */
    protected function runDataTransformation(ProcessingResult $processingResult, ImportDataTransformerInterface $dataTransformer, array $importModels): void
    {
        $table = $dataTransformer->getTable();
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $uniqueTargetIdentifierField = $importConfiguration->getSourceIdentifierField();
        $rawDataConverter = $dataTransformer->getRawDataConverter();
        $deletedField = $importConfiguration->getDeletedField();
        $languageField = $importConfiguration->getLanguageField();
        $transOrigPointerField = $importConfiguration->getTransOrigPointerField();
        $targetSourceValue = $this->getImportOptions()->getTargetImportSource();
        $targetSourceField = $importConfiguration->getTargetImportSourceField();
        foreach ($importModels as $model) {
            $data = $rawDataConverter->convert($model, $this->referenceResolver);
            if ($deletedField) {
                $data[$deletedField] = 0;
            }
            $data = $dataTransformer->postProcessConvertedData($model, $data);
            // The default language records are processed first, so the language parent should exist here
            if ($languageField && $transOrigPointerField && $model->getLanguageUid() > 0) {
                $identifier = $rawDataConverter->getImportIdentifier($model->getImportData());
                $referenceId = (int)$this->mapping->getTargetReferenceId($table, $uniqueTargetIdentifierField, (string)$identifier, 0);
                $data[$transOrigPointerField] = $referenceId;
            }
            if ($targetSourceField && $targetSourceValue) {
                $data[$targetSourceField] = $targetSourceValue;
            }
            $model->setTransformedData($data);
            if ($model->getFilesProcessed()) {
                $model->clearRawData();
            }
            if ($model->hasUnresolvedReferences()) {
                $processingResult->addModelWithUnresolvedReferences($model);
            }
            if ($unresolvedValues = $model->getUnresolvedValues()) {
                $combinedMessage = implode('; ', $unresolvedValues);
                $this->logger->error($combinedMessage, ['uid' => $model->getTargetRecordId(), 'import_identifier' => $model->getSourceRecordIdentifier()]);
            }
        }
    }

    /**
     * Executes post-processing tasks after the transformation process is completed. This method ensures that
     * many-to-one references for all tables processed by the data transformers are updated using the reference writer.
     *
     * @return void
     */
    protected function postProcessAfterTransformationCompleted(): void
    {
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            $table = $dataTransformer->getTable();
            $this->referenceWriter->updateManyToOneReferences($table);
        }
        $this->referenceWriter->updateManyToManyReferences($this->mapping);
    }
}
