<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Command;

use BrainAppeal\CampusEventsConnector\Importer\AbstractFileImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;

class CleanupFilesCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        Bootstrap::initializeBackendAuthentication();
        AbstractFileImporter::cleanupTemporaryFiles();
        return 0;
    }
}
