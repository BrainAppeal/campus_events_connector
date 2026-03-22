<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import;

use BrainAppeal\CampusEventsConnector\Import\Event\BeforeImportStartedEvent;
use Doctrine\DBAL\Exception;
use BrainAppeal\CampusEventsConnector\Import\DataCollection\AbstractDataCollection;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterDataCollectionCompletedEvent;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use BrainAppeal\CampusEventsConnector\Import\Event\ImportFinishEvent;
use BrainAppeal\CampusEventsConnector\Import\Event\ImportRunCompletedEvent;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportAlreadyRunningException;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportEntry;
use BrainAppeal\CampusEventsConnector\Import\Repository\ImportEntryManager;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ImportTargetRecordMapping;
use BrainAppeal\CampusEventsConnector\Import\Workflow\ImportRecordWorkflow;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

/**
 * Handles import operations such as stopping running import entries, processing data from import
 * providers, and managing output and logging for import processes.
 */
class Importer
{

    /**
     * @var ?OutputInterface
     */
    private ?OutputInterface $output = null;

    public function __construct(
        protected readonly ImportEntryManager   $importEntryManager,
        protected readonly ImportRecordWorkflow $processing,
        protected readonly LoggerInterface      $logger,
        protected EventDispatcherInterface      $eventDispatcher,
    ) {}

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Stop all import entries on the given page.
     * This is useful in case an import process has been exited without properly marking the import entry as not running
     *
     * @param int $pid The page ID to scope clean-up operations
     */
    public function stopImportEntriesMarkedAsRunning(int $pid): void
    {
        $this->importEntryManager->stopImportEntriesMarkedAsRunning($pid);
    }

    /**
     * Run import for the given import source and options
     *
     * @param AbstractDataCollection $dataCollection
     * @param AbstractImportOptions $importOptions
     * @return int The number of processed records
     * @throws Exception
     * @throws FolderDoesNotExistException
     * @throws ImportAlreadyRunningException
     */
    public function run(AbstractDataCollection $dataCollection, AbstractImportOptions $importOptions): int
    {
        $event = new BeforeImportStartedEvent($importOptions);
        $this->eventDispatcher->dispatch($event);
        $importSource = $importOptions->getImportSource();
        $startTime = microtime(true);
        $this->checkImportSourceAvailability($dataCollection, $importSource);
        if ($importOptions->isForceUpdate() || !$importOptions->supportsContinuedImport()) {
            $this->importEntryManager->forceStartOfNewImportEntry($importSource);
        }
        $forced = $importOptions->isForceUpdate() ? ' forced' : '';
        $this->writeOutput(sprintf('Starting%s import for source %s', $forced, $importSource));
        $dataSourceLastModified = $importOptions->isForceUpdate() ? time() : $dataCollection->getDataSourceLastModified();
        $importEntry = $this->importEntryManager->getCurrentImportEntry($importSource, $importOptions->getPid(), $dataSourceLastModified);
        $importOptions->setImportId($importEntry->getUid());
        $importEntry->setImportLimit($importOptions->getLimit());
        try {
            $processedRecordCount = $this->executeDataImport($dataCollection, $importEntry, $importOptions);
            $duration = round(microtime(true) - $startTime, 3);
            $infoMessage = sprintf('Loading duration: %s seconds', $duration);
            $this->writeOutput($infoMessage);
            $infoMessage = sprintf(
                'Executed import for source %s. %d of %d records processed. API data fetched for %d records. %d records skipped.',
                $importSource,
                $importEntry->getImportedRowCount(),
                $importEntry->getTotalRowCount(),
                $importEntry->getFullLoadedRowCount(),
                $importEntry->getSkippedRowCount(),
            );
            $this->writeOutput($infoMessage);
            $this->logger->info($infoMessage, ['importSource' => $importSource, 'duration' => $duration]);
            if ($importEntry->isImportFinished()) {
                $this->writeOutput(sprintf(
                    'Completed import for source %s.',
                    $importSource
                ));
            }
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Error during import: %s', $exception->getMessage()), ['exception' => $exception]);
            throw $exception;
        }
        return $processedRecordCount;
    }

    /**
     * Output a message with given verbosity
     *
     * @param string $message The message
     * @param int $verbosity The verbosity controls which messages are displayed
     */
    protected function writeOutput(string $message, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        if ($this->output !== null) {
            $debug = date('Y-m-d H:i:s') . ': ' . $message;
            $this->output->writeln($debug, OutputInterface::OUTPUT_NORMAL | $verbosity);
        }
    }

    /**
     * Executes the data import process for a specified import entry. This method
     * determines if this is a new import or a continuation and processes the import source
     * accordingly. It also handles stopping the import and providing relevant status updates.
     *
     * @param AbstractDataCollection $dataCollection An instance responsible for processing the import source.
     * @param ImportEntry $importEntry The import entry object for which the data import is being performed.
     * @param AbstractImportOptions $importOptions
     * @return int Returns the total number of records processed during the import operation.
     */
    private function executeDataImport(AbstractDataCollection $dataCollection, ImportEntry $importEntry, AbstractImportOptions $importOptions): int
    {
        $isStartOfNewImport = !$importEntry->isFirstImportDone();
        if ($isStartOfNewImport) {
            $importEntry->setFirstImportDone(true);
            $infoMessage = sprintf('Importing data for new import entry %d. This may take a while.', $importEntry->getUid());
        } else {
            $infoMessage = sprintf('Continuing import for previously started import entry %d. This may take a while.', $importEntry->getUid());
        }
        $this->writeOutput($infoMessage);
        $processedRecordCount = 0;
        $collectedRowCount = $dataCollection->collectRawData($isStartOfNewImport);
        $configuredLimit = $importOptions->getLimit();
        $limit = $configuredLimit > 0 ? $configuredLimit - $collectedRowCount : null;
        if ($dataCollection->isComplete()) {
            $infoMessage = sprintf('Data collection of %d rows from data source %s finished', $collectedRowCount, $importOptions->getImportSource());
            $this->writeOutput($infoMessage);
            $this->logger->info($infoMessage, ['importSource' => $importOptions->getImportSource()]);
            $afterDataCollectionCompletedEvent = new AfterDataCollectionCompletedEvent($importOptions);
            $this->eventDispatcher->dispatch($afterDataCollectionCompletedEvent);
            if ($limit === null || $limit > 0) {
                $processedRecordCount = $this->processing->transformCollectedData($importEntry, $limit);
                $mapping = $this->processing->getMapping();
                $afterDataTransformationCompletedEvent = new AfterRecordsWrittenEvent($importOptions, $mapping);
                $this->eventDispatcher->dispatch($afterDataTransformationCompletedEvent);
                $this->writeCreateOrUpdatedMessage($mapping);
                $processingErrors = $this->processing->getErrorsByTable();
                if (!empty($processingErrors)) {
                    $this->writeOutput('There were errors processing the collected data:');
                    foreach ($processingErrors as $table => $errorsForTable) {
                        foreach ($errorsForTable as $error) {
                            $this->writeOutput($table . ':' . $error);
                        }
                    }
                }
            }
        }
        $stopEvent = new ImportRunCompletedEvent($importOptions, $importEntry);
        $this->eventDispatcher->dispatch($stopEvent);
        $importEntry->setRunning(false);
        $this->importEntryManager->updateActiveEntry($importEntry);
        if ($importEntry->isImportFinished()) {
            $event = new ImportFinishEvent($importOptions, $importEntry);
            $this->eventDispatcher->dispatch($event);
            if ($event->getDeletedRowCount() > 0) {
                $infoMessage = sprintf('Import workflow: %d rows were deleted.', $event->getDeletedRowCount());
                $this->writeOutput($infoMessage);
            } else {
                $this->writeOutput('Import workflow: No records were deleted.');
            }
            if ($importEntry->getTotalRowCount() > 0 && $importEntry->getTotalRowCount() - $importEntry->getSkippedRowCount() === 0) {
                $infoMessage = 'The import has been finished, because no new data were found.';
                $this->writeOutput($infoMessage);
            }
        }
        return $processedRecordCount;
    }

    /**
     * Generates and writes an informational message summarizing the number of records
     * created or updated, grouped by source type, based on the provided mapping object.
     * If no records were created or updated, appropriate messages are also written.
     *
     * @param ImportTargetRecordMapping $mapping The mapping object containing counts
     *                                           of created and updated records, grouped by type.
     * @return void
     */
    private function writeCreateOrUpdatedMessage(ImportTargetRecordMapping $mapping): void
    {
        $groupTextsCreated = [];
        $groupTextsUpdated = [];
        $totalCreatedCount = 0;
        $totalUpdatedCount = 0;
        foreach ($mapping->getCountCreatedOrUpdatedByType() as $sourceType => $countValues) {
            $totalCreatedCount += $countValues['created'];
            $totalUpdatedCount += $countValues['updated'];
            $groupTextsCreated[] = sprintf('%s: %d', $sourceType, $countValues['created']);
            $groupTextsUpdated[] = sprintf('%s: %d', $sourceType, $countValues['updated']);
        }
        if ($totalCreatedCount > 0) {
            $infoMessage = sprintf('Import workflow: %d rows were created: %s', $totalCreatedCount, implode(', ', $groupTextsCreated));
            $this->writeOutput($infoMessage);
        } else {
            $this->writeOutput('Import workflow: No new records were created.');
        }
        if ($totalUpdatedCount > 0) {
            $infoMessage = sprintf('Import workflow: %d rows were updated: %s', $totalUpdatedCount, implode(', ', $groupTextsUpdated));
            $this->writeOutput($infoMessage);
        } else {
            $this->writeOutput('Import workflow: No records were updated.');
        }
    }

    /**
     * Checks if the import source is available and throws an exception if not.
     *
     * @param AbstractDataCollection $initialization
     * @param string $importSource
     */
    private function checkImportSourceAvailability(AbstractDataCollection $initialization, string $importSource): void
    {
        if (!$initialization->canProvide()) {
            $message = sprintf('The import source %s either does not exist, is not readable or is not configured correctly!', $importSource);
            $this->writeOutput($message);
            $this->logger->error($message, ['importSource' => $importSource]);
            throw new \InvalidArgumentException(sprintf($message, $importSource));
        }
    }

}
