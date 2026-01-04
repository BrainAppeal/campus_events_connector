<?php


declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport;

use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiConnector;
use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiDataCollection;
use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\TranslationHandler;
use BrainAppeal\CampusEventsConnector\CeImport\Model\ImportOptions;
use BrainAppeal\CampusEventsConnector\Importer\PostImportHookInterface;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Importer;
use BrainAppeal\CampusEventsConnector\Import\ImportOptionsFactory;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class responsible for running the import process for Campus Events data.
 * This class handles the initialization, execution of data imports, and post-import operations.
 */
readonly class ImportRunner
{

    public function __construct(
        private Importer               $importer,
        private ImportOptionsFactory   $importOptionsFactory,
        private DataTransformerFactory $dataTransformerFactory,
        private CeApiDataCollection    $dataCollection,
        private CeApiConnector         $apiConnector,
        private TranslationHandler     $translationHandler
    )
    {
    }

    /**
     * Bootstrap running of upgradeWizards
     */
    protected function bootstrap(): void
    {
        if (Environment::isCli()) {
            Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
            Bootstrap::initializeBackendAuthentication();
        }
    }

    /**
     * Executes the data import process for a given storage page and configuration.
     *
     * @param int $pid The storage page ID where data will be imported.
     * @param array $config The configuration options for the import process.
     * @param InputInterface|null $input The input interface for interaction, defaults to ArgvInput if null.
     * @param OutputInterface|null $output The output interface for messages, defaults to ConsoleOutput if null.
     * @return int The number of records processed during the import.
     * @throws \InvalidArgumentException If the specified storage page does not exist.
     */
    public function run(int $pid, array $config, ?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        if (null === $output) {
            $output = new ConsoleOutput();
        }
        if (null === $input) {
            $input = new ArgvInput();
        }
        $io = new SymfonyStyle($input, $output);
        $io->title('Import raw data from Campus Events API');
        $this->bootstrap();
        $pageRecord = BackendUtility::getRecord('pages', $pid);
        if (!$pageRecord) {
            throw new \InvalidArgumentException(sprintf('The storage page with id %s does not exist!', $pid));
        }
        $io->writeln(sprintf('Importing data to storage page %s [%d]', $pageRecord['title'], $pid));
        $config['targetImportSource'] = '';
        $importOptions = new ImportOptions($config);
        if (isset($config['verbosity'])) {
            $importOptions->setVerbosity((int)$config['verbosity']);
        }
        $this->importOptionsFactory->set($importOptions);
        $importer = $this->importer;
        $importer->setOutput($io);
        if ($importOptions->isStopPrevious()) {
            $importer->stopImportEntriesMarkedAsRunning($pid);
        }

        // Set the group key for data transformers
        $this->apiConnector->setBaseUrl($importOptions->getBaseUri());
        $this->apiConnector->setApiKey($importOptions->getApiKey());
        $this->dataCollection->setApiConnector($this->apiConnector);
        $languageMap = $this->initializeLanguageMap($pid);
        $this->dataCollection->setLanguageMap($languageMap);

        // Set the group key for data transformers
        $this->dataTransformerFactory->setGroupKey($importOptions->getImportSource());
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            if ($dataTransformer->hasFileTransformations()) {
                $dataTransformer->getFileDataTransformerHelper()->setBaseUri($importOptions->getBaseUri());
            }
            $this->fixImportSourceNames($dataTransformer, $importOptions);
        }

        $processedRecordCount = $importer->run($this->dataCollection, $importOptions);
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            $this->updateImportedAtField($dataTransformer);
        }
        $this->callHooks($importOptions->getPid());

        $message = $this->buildSummaryMessage($processedRecordCount, $importOptions);
        $io->success(trim($message));
        return $processedRecordCount;
    }

    private function fixImportSourceNames(ImportDataTransformerInterface $dataTransformer, ImportOptions $importOptions): void
    {
        $targetSourceValue = $importOptions->getTargetImportSource();
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $targetSourceField = $importConfiguration->getTargetImportSourceField();
        $baseUri = $importOptions->getBaseUri();
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        if ($targetSourceField && $targetSourceValue) {
            $tableName = $importConfiguration->getTableName();
            $connection = $connectionPool->getConnectionForTable($tableName);
            $updateImportSourceSql = "UPDATE $tableName SET ce_import_source = ? WHERE (ce_import_source = ? OR (ce_import_id > 0 AND ce_import_source IS NULL))";
            $connection->executeStatement($updateImportSourceSql, [$targetSourceValue, $baseUri]);
        }
    }

    /**
     * Update the imported_at field for the
     */
    private function updateImportedAtField(ImportDataTransformerInterface $dataTransformer): void
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $tableName = $dataTransformer->getTable();
        $connection = $connectionPool->getConnectionForTable($tableName);
        $schemaManager = $connection->createSchemaManager();
        $tableColumInfo = $schemaManager->listTableColumns($tableName);
        foreach ($tableColumInfo as $column) {
            if ($column->getName() === 'ce_imported_at') {
                $updateImportSourceSql = "UPDATE $tableName SET ce_imported_at = tstamp WHERE ce_import_id > 0 AND (ce_imported_at IS NULL OR ce_imported_at < tstamp)";
                $connection->executeStatement($updateImportSourceSql);
                break;
            }
        }
    }

    /**
     * Initializes a map of language codes to their corresponding language IDs for a specific page.
     *
     * @param int $pid The ID of the page for which the language map is being generated.
     * @return array<string, int> An associative array mapping language codes to language IDs.
     */
    private function initializeLanguageMap(int $pid): array
    {
        // The default language of campus events is German
        $languageMap = ['de' => 0];
        $languageOverlays = $this->translationHandler->getPageLanguageOverlays($pid);
        foreach ($languageOverlays as $languageId => $languageOverlay) {
            $languageMap[$languageOverlay['language_code']] = $languageId;
        }
        return $languageMap;
    }

    /**
     * Builds the summary message for the import process.
     * @param int $processedRowCount
     * @param AbstractImportOptions $importOptions
     * @return string
     */
    protected function buildSummaryMessage(int $processedRowCount, AbstractImportOptions $importOptions): string
    {
        $importedRowCount = $this->dataCollection->getTotalRowsToImport();
        $message = '';
        if ($processedRowCount > 0) {
            $message .= sprintf('Processed %d raw import records.', $processedRowCount);
        }
        if ($importedRowCount > 0) {
            $message .= sprintf(' Added %d raw records from the API to the local import table.', $importedRowCount);
        }
        $apiCallsCount = $this->dataCollection->getNumberOfApiCallsMade();
        if ($apiCallsCount > 0) {
            $cacheApiCallsCount = $this->dataCollection->getNumberOfCachedApiCallsMade();
            if ($cacheApiCallsCount > 0) {
                $realApiCallsCount = $apiCallsCount - $cacheApiCallsCount;
                $message .= sprintf(' DEV mode: %d records were loaded from the local API cache.', $cacheApiCallsCount);
                $message .= sprintf(' %d real API calls were made.', $realApiCallsCount);
            } else {
                $message .= sprintf(' %d API calls were made.', $apiCallsCount);
            }
            $developmentLocalCacheAgeInDays = $importOptions->getDevelopmentLocalCacheAgeInDays();
            if ($developmentLocalCacheAgeInDays > 0 && ($relativeLocalCachePath = $importOptions->getRelativeLocalCachePath()) && Environment::getContext()->isDevelopment()) {
                $localCachePath = Environment::getVarPath() . '/' . ltrim($relativeLocalCachePath, '/');
                $message .= sprintf(' (only in DEVELOPMENT context: fetched API data are stored in %s to prevent excessive API calls.)', $localCachePath);
            }
        }
        return $message;
    }

    /**
     * Executes registered hooks after the import process.
     * Alternatively, you can implement an event listener for ImportFinishEvent
     *
     * @param int $targetPid The target page ID to be passed to the hook methods.
     * @return void
     */
    private function callHooks(int $targetPid): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if ($hookObj instanceof PostImportHookInterface || method_exists($hookObj, 'postImport')) {
                    $hookObj->postImport($targetPid);
                }
            }
        }
    }
}
