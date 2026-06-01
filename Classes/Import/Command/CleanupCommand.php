<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Command;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Finisher\CleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Represents a command to clean up imported data from an import source.
 * Provides options to either delete old records or truncate tables based on user input.
 */
#[AsCommand(
    name: 'ce:cleanup',
    description: 'Campus Events: Cleanup for imported data',
)]
class CleanupCommand extends Command
{
    public function __construct(
        private readonly CleanupService $cleanupService,
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
        $groupKeys = implode(', ', $this->dataTransformerFactory->getRegisteredGroupKeys());
        $this
            ->setHelp('
Cleans up imported data.

Import sources: ' . $groupKeys . '

By default only old records are deleted.

If the --truncate-tables option is set, the tables containing the import entries and raw data are truncated instead.
If the --truncate-all-tables option is set, all tables related to the import process are truncated.
This is useful for testing purposes or a full data refresh.
')
            ->addArgument('import-source', InputArgument::OPTIONAL, 'The import source to clean up')
            ->addOption(
                'confirm',
                'c',
                InputOption::VALUE_NONE,
                'If the environment is not development, you must confirm the cleanup by setting this option before truncating all tables'
            )
            ->addOption(
                'truncate-tables',
                't',
                InputOption::VALUE_NONE,
                'Truncate the import tables instead of only deleting old records'
            )
            ->addOption(
                'truncate-all-tables',
                'a',
                InputOption::VALUE_NONE,
                'Additionally truncate all other tables related to the import process'
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
     * Executes the command to clean up or truncate tables based on the provided options.
     *
     * @param InputInterface $input The input interface to retrieve options and arguments.
     * @param OutputInterface $output The output interface to display messages to the user.
     *
     * @return int Returns the command status code. Command::SUCCESS on successful execution.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->bootstrap();
        $io->title($this->getDescription());
        $groupKeys = $this->dataTransformerFactory->getRegisteredGroupKeys();
        if (count($groupKeys) === 0) {
            $output->writeln('<error>No import sources are configured!</error>');
            return Command::INVALID;
        }
        $importSource = $input->getArgument('import-source');
        if (!$importSource) {
            if (count($groupKeys) === 1) {
                $importSource = current($groupKeys);
            } else {
                $output->writeln(sprintf('<error>Please provide the import source argument. Possible values are: %s</error>', implode(', ', $groupKeys)));
                return Command::INVALID;
            }
        } elseif (!in_array($importSource, $groupKeys, true)) {
            $output->writeln(sprintf('<error>The import source "%s" is not configured! The import source must be one of %s</error>', $importSource, implode(', ', $groupKeys)));
            return Command::INVALID;
        }
        $truncateAllTables = (bool)$input->getOption('truncate-all-tables');
        $truncateTables = $truncateAllTables || $input->getOption('truncate-tables');
        $isConfirmationRequired = $truncateAllTables && !$input->getOption('confirm') && !Environment::getContext()->isDevelopment();
        if ($isConfirmationRequired) {
            $io->warning('This command will truncate all tables related to the import process. Please confirm by setting the --confirm option.');
            return Command::INVALID;
        }

        if ($truncateTables) {
            $truncatedTables = $this->cleanupService->truncateTables($truncateAllTables, false, $importSource);
            $io->success(sprintf('Truncated all import tables: %s', implode(', ', $truncatedTables)));
        } else {
            $this->cleanupService->cleanUpOldRecords();
            $io->success(sprintf('Deleted all raw import data that was older than 1 week'));
        }

        return Command::SUCCESS;
    }
}
