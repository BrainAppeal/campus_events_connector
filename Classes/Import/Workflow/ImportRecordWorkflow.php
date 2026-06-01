<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Workflow;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\PostProcessBatchEvent;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportOptionsConfigurationException;
use BrainAppeal\CampusEventsConnector\Import\Exception\RecordInvalidException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ReferenceNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ProcessingResult;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportRecordReader;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ReferenceResolver;
use BrainAppeal\CampusEventsConnector\Import\Writer\ReferenceWriter;
use BrainAppeal\CampusEventsConnector\Import\Writer\TargetRecordWriter;
use Doctrine\DBAL\Exception;
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
    public function __construct(
        protected ImportEntryManager $importEntryManager,
        protected DataTransformerFactory $dataTransformerFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected TargetRecordWriter $targetRecordPersister,
        protected LoggerInterface $logger,
        protected ReferenceResolver $referenceResolver,
        protected ReferenceWriter $referenceWriter
    ) {}

    /**
     * Transforms the collected date for the current import operation.
     * If the current import has a total limit of processed tows set, the given collected row count is used to lower
     * the limit of rows to be transformed. This prevents the import process from running too long
     *
     * @param ImportContext $context
     * @param ?int $maxRowsToProcess The maximum number of rows to transform. If not set, all available rows will be processed
     * @return int The total number of rows processed during this execution.
     * @throws Exception
     */
    public function transformCollectedData(ImportContext $context, ?int $maxRowsToProcess = null): int
    {
        $importEntry = $context->getImportEntry();
        $importId = $importEntry->getUid();
        $importRecordWriter = $this->importEntryManager->getImportRecordWriter();
        // 1. Set the target records for all existing records
        foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
            $importRecordWriter->updateTargetRecordIdsForType($context, $dataTransformer->getImportConfiguration());
            $this->referenceResolver->refreshTargetRecordIdMapping($context, $dataTransformer->getImportConfiguration());
        }
        $totalProcessedRowCount = 0;
        $sumRowsByLanguage = $this->importEntryManager->getImportRecordReader()->countRowsForProcessingByLanguage($importId);
        if (!$maxRowsToProcess) {
            $limit = 0;
            foreach ($sumRowsByLanguage as $sumRow) {
                $limit += $sumRow['sum_data_not_processed'] + $sumRow['sum_files_not_processed'];
            }
        } else {
            $limit = $maxRowsToProcess;
        }
        $memoryBefore = memory_get_usage();
        $startTime = microtime(true);
        foreach ($sumRowsByLanguage as $sumRow) {
            $language = (int)$sumRow['sys_language_uid'];
            $countUnmappedRows = $sumRow['sum_unmapped'];
            $countMappedRows = $sumRow['sum_mapped'];
            $countFilesNotProcessed = $sumRow['sum_files_not_processed'];
            // Process unmapped rows first
            if ($countUnmappedRows > 0) {
                $totalProcessedRowCount += $this->processImportRowsInBatches($context, $language, $limit, true);
                // Set the target records for all existing records
                foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
                    $importRecordWriter->updateTargetRecordIdsForType($context, $dataTransformer->getImportConfiguration());
                    $this->referenceResolver->refreshTargetRecordIdMapping($context, $dataTransformer->getImportConfiguration());
                }
            }
            // Then process mapped rows
            if ($limit > $totalProcessedRowCount && ($countMappedRows > 0 || $countFilesNotProcessed > 0)) {
                $totalProcessedRowCount += $this->processImportRowsInBatches($context, $language, $limit, false);
            }
            if ($limit <= $totalProcessedRowCount) {
                break;
            }
        }
        if ($context->options->isEnableStatistics()) {
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
        $this->postProcessAfterTransformationCompleted($context);
        return $totalProcessedRowCount;
    }

    /**
     * Processes import rows in batches for a given import entry, up to the specified limit.
     *
     * This method fetches a limited number of rows in each iteration, processes them,
     * and updates their processing state. The process continues until either the limit
     * is reached or there are no more rows left to process.
     *
     * @param ImportContext $context
     * @param int $language The language identifier of the rows to process.
     * @param int $limit The maximum number of rows to process.
     * @param bool $unmappedRowsOnly Indicates whether to process only unmapped rows or all rows.
     * @return int The total number of rows processed.
     * @throws Exception
     */
    protected function processImportRowsInBatches(ImportContext $context, int $language, int $limit, bool $unmappedRowsOnly): int
    {
        $importRowType = $unmappedRowsOnly ? ImportRecordReader::IMPORT_ROW_TYPE_UNMAPPED : ImportRecordReader::IMPORT_ROW_TYPE_MAPPED;
        $totalProcessedRowCount = 0;
        // If the limit is 0, there is nothing to process
        $importOptions = $context->options;
        $importId = $context->getImportId();
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
            $processingResult = $this->initializeProcessingResult($context, $rows);
            // 2. Map dependencies for all existing target records. ID's of newly added rows will be added to the mapping later.
            $this->initializeDependencyMapping($context, $processingResult);
            // 3. Process new rows
            $this->processNewRows($context, $processingResult);
            // 4. Now update the existing rows with changes
            $this->processUpdatedRows($context, $processingResult);
            // 5. Dispatch events post-processing
            $event = new PostProcessBatchEvent($context, $processingResult);
            $this->eventDispatcher->dispatch($event);
            // Update the processed import rows according to their current processing state
            $importRecordWriter->updateProcessingStatesForImportRows($context, $processingResult->getAllRows());
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
     * Initializes the ProcessingResult object based on the provided rows. The given rows are grouped by source type and processed status.
     *
     * @param ImportContext $context
     * @param ImportRecordModel[] $rows An array of models containing data to be processed. Each model is expected to provide methods
     *                    for accessing source type, data processing status, target record ID, and file processing status.
     * @return ProcessingResult The populated ProcessingResult object containing grouped rows and process files list.
     */
    protected function initializeProcessingResult(ImportContext $context, array $rows): ProcessingResult
    {
        $mapping = $context->getTargetRecordMapping();
        $processingResult = new ProcessingResult($rows, $mapping);
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
     * @param ImportContext $context
     * @param ProcessingResult $processingResult The result object containing the list of data types that have been processed.
     *                                            Each data type is used to fetch corresponding dependencies and map them.
     */
    protected function initializeDependencyMapping(ImportContext $context, ProcessingResult $processingResult): void
    {
        foreach ($processingResult->getDataTypesProcessed() as $targetTable) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            $importConfiguration = $dataTransformer->getImportConfiguration();
            $dependenciesToOtherTables = $importConfiguration->getDependenciesToOtherTables();
            $internalDependencies = $importConfiguration->getInternalDependencies();
            if (!empty($internalDependencies)) {
                foreach ($internalDependencies as $mapEntry) {
                    $matchField = $mapEntry->get('foreign_match_field');
                    $dependenciesToOtherTables[$targetTable][] = $matchField;
                }
                $dependenciesToOtherTables[$targetTable] = array_unique($dependenciesToOtherTables[$targetTable]);
            }
            if (!empty($dependenciesToOtherTables)) {
                $this->referenceResolver->mapDependencies($context, $dependenciesToOtherTables);
            }
        }
    }

    /**
     * Processes new rows to be added based on their dependencies and source types.
     * The method processes rows in two stages:
     * 1. Rows without dependencies to other import tables are transformed and processed first.
     * 2. Rows with dependencies to other import tables are processed after the dependencies have been resolved.
     *
     * @param ImportContext $context
     * @param ProcessingResult $processingResult The ProcessingResult instance containing grouped rows and tracking of processed row counts.
     *                                           Each group of rows is organized by source type and expected to be processed accordingly.
     * @throws Exception
     */
    protected function processNewRows(ImportContext $context, ProcessingResult $processingResult): void
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
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            $importConfiguration = $dataTransformer->getImportConfiguration();
            if (empty($importConfiguration->getDependenciesToOtherTables())) {
                $this->processNewImportModels($context, $processingResult, $targetTable, $importModels);
            }
        }
        /*
         * 2.2. In the second iteration only data types with dependencies to other import tables are processed.
         * When we reach this point, we can assume that all dependencies to other tables have been resolved.
         * Only dependencies to the same table are not resolved yet.
         */
        foreach ($groupedRowsNew as $targetTable => $importModels) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            $importConfiguration = $dataTransformer->getImportConfiguration();
            $dependenciesToOtherTables = $importConfiguration->getDependenciesToOtherTables();
            if (!empty($dependenciesToOtherTables)) {
                $this->processNewImportModels($context, $processingResult, $targetTable, $importModels);
            }
        }
        // Update the target record IDs for newly added rows; this is only needed if the import rows are loaded again
        $targetTables = array_keys($groupedRowsNew);
        $importRecordWriter = $this->importEntryManager->getImportRecordWriter();
        foreach ($targetTables as $targetTable) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            $importRecordWriter->updateTargetRecordIdsForType($context, $dataTransformer->getImportConfiguration());
        }
    }

    /**
     * Processes updated rows based on the data available in the given ProcessingResult object. This involves transforming
     * the data and persisting updated rows while keeping track of the processed row count.
     *
     * @param ImportContext $context
     * @param ProcessingResult $processingResult The ProcessingResult object containing grouped rows organized by source type,
     *                                           which are to be processed and counted as updated.
     */
    protected function processUpdatedRows(ImportContext $context, ProcessingResult $processingResult): void
    {
        $groupedRowsUpdate = $processingResult->getGroupedRowsUpdate();
        foreach ($groupedRowsUpdate as $targetTable => $importModels) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $targetTable);
            $processingResult->incrementProcessedRowCount(count($importModels));
            $modelsWithTransformedData = [];
            foreach ($importModels as $model) {
                if ($modelWithTransformedData = $this->runDataTransformation($context, $dataTransformer, $model)) {
                    $modelsWithTransformedData[] = $modelWithTransformedData;
                }
            }
            $writtenRecordCount = $this->targetRecordPersister->updateRecords(
                $context,
                $dataTransformer->getImportConfiguration(),
                $modelsWithTransformedData
            );
            $processingResult->incrementRecordCount($writtenRecordCount, false);
        }
    }

    /**
     * Processes the given import models
     *
     * @param ImportContext $context
     * @param ProcessingResult $processingResult
     * @param string $table
     * @param ImportRecordModel[] $importModels
     * @throws ReferenceNotFoundException
     */
    protected function processNewImportModels(ImportContext $context, ProcessingResult $processingResult, string $table, array $importModels): void
    {
        $dataTransformer = $this->dataTransformerFactory->getDataTransformerByContextAndTable($context, $table);
        $processingResult->incrementProcessedRowCount(count($importModels));
        $writtenRecordCount = 0;
        // Each model is inserted immediately, so the internal references can be resolved
        foreach ($importModels as $model) {
            if ($modelWithTransformedData = $this->runDataTransformation($context, $dataTransformer, $model)) {
                $this->targetRecordPersister->createRecord(
                    $context,
                    $dataTransformer->getImportConfiguration(),
                    $modelWithTransformedData,
                    $this->referenceResolver
                );
                ++$writtenRecordCount;
            }
        }
        $processingResult->incrementRecordCount($writtenRecordCount, true);
    }

    /**
     * Executes the data transformation process for the provided model.
     *
     * @param ImportContext $context
     * @param ImportDataTransformerInterface $dataTransformer
     * @param ImportRecordModel $model
     * @return ?ImportRecordModel Model with transformed data or null if transformation failed
     * @throws ReferenceNotFoundException
     */
    protected function runDataTransformation(ImportContext $context, ImportDataTransformerInterface $dataTransformer, ImportRecordModel $model): ?ImportRecordModel
    {
        $table = $dataTransformer->getTable();
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $uniqueTargetIdentifierField = $importConfiguration->getSourceIdentifierField();
        $rawDataConverter = $dataTransformer->getRawDataConverter();
        $deletedField = $importConfiguration->getDeletedField();
        $languageField = $importConfiguration->getLanguageField();
        $transOrigPointerField = $importConfiguration->getTransOrigPointerField();
        $targetSourceField = $importConfiguration->getTargetImportSourceField();
        try {
            $data = $rawDataConverter->convert($context, $model, $this->referenceResolver);
        } catch (RecordInvalidException $e) {
            $this->logger->warning(sprintf('Skipping record %s: %s because the record is invalid: %s', $model->getTargetTable(), $model->getSourceRecordIdentifier(), $e->getMessage()));
            return null;
        }
        if ($deletedField) {
            $data[$deletedField] = 0;
        }
        $data = $dataTransformer->postProcessConvertedData($model, $data);
        // The default language records are processed first, so the language parent should exist here
        if ($languageField && $transOrigPointerField && $model->getLanguageUid() > 0) {
            $identifier = $rawDataConverter->getImportIdentifier($model->getImportData());
            $mapping = $context->getTargetRecordMapping();
            $referenceId = (int)$mapping->getTargetReferenceId($table, $uniqueTargetIdentifierField, (string)$identifier, 0);
            $data[$transOrigPointerField] = $referenceId;
        }
        if ($targetSourceField && ($targetSourceValue = $context->options->getTargetImportSource())) {
            $data[$targetSourceField] = $targetSourceValue;
        }
        $model->setTransformedData($data);
        if ($model->getFilesProcessed()) {
            $model->clearRawData();
        }
        if ($unresolvedValues = $model->getUnresolvedValues()) {
            $combinedMessage = implode('; ', $unresolvedValues);
            $this->logger->error($combinedMessage, ['uid' => $model->getTargetRecordId(), 'import_identifier' => $model->getSourceRecordIdentifier()]);
        }
        return $model;
    }

    /**
     * Executes post-processing tasks after the transformation process is completed. This method ensures that
     * many-to-one references for all tables processed by the data transformers are updated using the reference writer.
     *
     * @param ImportContext $context
     * @throws RecordInvalidException
     * @throws ImportOptionsConfigurationException
     */
    protected function postProcessAfterTransformationCompleted(ImportContext $context): void
    {
        $this->referenceResolver->tryResolvingUnresolvedReferences($context);
        foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
            $table = $dataTransformer->getTable();
            $this->referenceWriter->updateManyToOneReferences($table);
        }
        $this->referenceWriter->updateManyToManyReferences($context);
    }
}
