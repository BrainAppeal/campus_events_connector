<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport;

use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiConnector;
use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiDataCollection;
use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\TranslationHandler;
use BrainAppeal\CampusEventsConnector\CeImport\Model\ImportOptions;
use BrainAppeal\CampusEventsConnector\Import\Importer;
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

/**
 * A class responsible for running the import process for Campus Events data.
 * This class handles the initialization, execution of data imports, and post-import operations.
 */
readonly class ImportRunner
{
    public function __construct(
        private Importer $importer,
        private CeApiDataCollection $dataCollection,
        private CeApiConnector $apiConnector,
        private TranslationHandler $translationHandler
    ) {}

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
        if (!$output instanceof OutputInterface) {
            $output = new ConsoleOutput();
        }
        if (!$input instanceof InputInterface) {
            $input = new ArgvInput();
        }
        $io = new SymfonyStyle($input, $output);
        $io->title('Import raw data from Campus Events API');
        $this->bootstrap();
        $pageRecord = BackendUtility::getRecord('pages', $pid);
        if (!$pageRecord) {
            throw new \InvalidArgumentException(sprintf('The storage page with id %s does not exist!', $pid), 7435615918);
        }
        $io->writeln(sprintf('Importing data to storage page %s [%d]', $pageRecord['title'], $pid));
        $config['targetImportSource'] = '';
        $importOptions = new ImportOptions($config);
        if (isset($config['verbosity'])) {
            $importOptions->setVerbosity((int)$config['verbosity']);
        }
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

        $processedRecordCount = $importer->run($this->dataCollection, $importOptions);

        $message = $this->buildSummaryMessage($processedRecordCount, $importOptions);
        $io->success(trim($message));
        return $processedRecordCount;
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
}
