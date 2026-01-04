<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Command;

use BrainAppeal\CampusEventsConnector\CeImport\ImportRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCommand extends Command
{

    /**
     * Configure the command by defining the name, options, and arguments
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                'baseuri',
                InputArgument::REQUIRED,
                'The base uri of the Campus Events API, e.g. https://demo-staging.campus-events.com/'
            )->addArgument(
                'pid',
                InputArgument::REQUIRED,
                'The target page id for the imported events'
            )
            ->addArgument(
                'apikey',
                InputArgument::REQUIRED,
                'The API key; only needed for non-public properties',
            )
            ->addArgument(
                'storageId',
                InputArgument::OPTIONAL,
                'The storage id; default is 1 (usually fileadmin)',
                1
            )
            ->addArgument(
                'storageFolder',
                InputArgument::OPTIONAL,
                'The storage folder (relative to storage root)',
                'campus_events_import/task-1793/'
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseUri = $input->getArgument('baseuri');
        $targetPid = (int)$input->getArgument('pid');
        $storageId = (int)$input->getArgument('storageId');
        $storageFolder = $input->getArgument('storageFolder');
        $apiKey = $input->getArgument('apikey');
        $config = [
            'importStoragePid' => $targetPid,
            'forceUpdate' => $input->hasOption('force-update') && $input->getOption('force-update'),
            'stopPrevious' => $input->hasOption('stop-previous') && $input->getOption('stop-previous'),
            'completeUpdate' => true,
            'importTargetResourceIdentifier' => '',
            'baseUri' => $baseUri,
            'apiKey' => $apiKey,
            'debug' => $input->hasOption('debug') && $input->getOption('debug'),
        ];
        if (!empty($this->storageId) && !empty($this->storageFolder)) {
            $config['importTargetResourceIdentifier'] = $storageId . ':' . trim($storageFolder, '/') . '/';
        }
        $importRunner = GeneralUtility::makeInstance(ImportRunner::class);
        $importRunner->run($targetPid, $config, $input, $output);

        return Command::SUCCESS;
    }
}
