<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Command;

use BrainAppeal\CampusEventsConnector\Import\DataCollection\AbstractDataCollection;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportOptionsConfigurationException;
use BrainAppeal\CampusEventsConnector\Import\Importer;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;

/**
 * This command class is designed to import raw data from the ZIS API into
 * the TYPO3 database. It provides configurable options to control the import
 * process, including the ability to set limits, force updates, and handle full
 * API dumps. Additionally, it supports resetting any previously running import
 * process before starting a new one.
 *
 * Key functionality:
 * - Define mandatory arguments and optional parameters.
 * - Validate the import configuration before execution.
 * - Support different import modes like regular incremental updates or full dumps.
 * - Provide messages and status regarding processed operations via CLI output.
 *
 * Usage of this command requires proper configuration of the associated TYPO3 extension.
 */
abstract class AbstractImportCommand extends Command
{
    /**
     * Fallback value for import limits. Only used if neither the command options nor the extension configuration
     * have settings defined
     */
    public const CHUNK_SIZE = 50;

    public function __construct(
        protected readonly AbstractDataCollection $dataCollection,
        protected readonly Importer $importer,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly DataTransformerFactory $dataTransformerFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'pid',
                InputArgument::REQUIRED,
                'the storage pid'
            )
            ->addOption(
                'force-update',
                'f',
                InputOption::VALUE_NONE,
                'Force a full update of all records, ignoring timestamps and data hashes'
            )
            ->addOption(
                'stop-previous',
                's',
                InputOption::VALUE_NONE,
                'Reset the running flag of any current import process'
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                'Enable debug mode'
            );
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
     * Initialize import options based on input
     * @throws ImportOptionsConfigurationException
     */
    abstract protected function initializeImportOptions(InputInterface $input): AbstractImportOptions;

    /**
     * Run the import command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->bootstrap();
        $io->title($this->getDescription());
        $pid = (int)$input->getArgument('pid');
        $pageRecord = BackendUtility::getRecord('pages', $pid);
        if (!$pageRecord) {
            $output->writeln(sprintf('<error>The storage page with id %s does not exist!</error>', $pid));
            return Command::INVALID;
        }
        try {
            $importOptions = $this->initializeImportOptions($input);
        } catch (ImportOptionsConfigurationException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::INVALID;
        }
        $output->writeln(sprintf('Importing data to storage page %s [%d]', $pageRecord['title'], $pid));
        $importOptions->setVerbosity($output->getVerbosity());
        if ($output->isVerbose()) {
            $importOptions->enableStatistics();
        }
        $importer = $this->importer;
        $importer->setOutput($io);
        if ($importOptions->isStopPrevious()) {
            $importer->stopImportEntriesMarkedAsRunning($pid);
        }

        $processedRecordCount = $importer->run($this->dataCollection, $importOptions);

        $message = $this->buildSummaryMessage($processedRecordCount, $importOptions);
        $io->success(trim($message));

        return Command::SUCCESS;
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
