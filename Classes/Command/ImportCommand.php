<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Command;

use BrainAppeal\CampusEventsConnector\Importer\PostImportHookInterface;
use BrainAppeal\CampusEventsConnector\Utility\CacheUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCommand extends Command
{
    private const API_VERSION_ABOVE_227 = 'above-2-27-0';

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
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
            ->addArgument(
                'apiversion',
                InputArgument::OPTIONAL,
                'The API version; either "above-2-27-0" or "below-2-27-0"',
                self::API_VERSION_ABOVE_227
            );
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        Bootstrap::initializeBackendAuthentication();
        $baseUri = $input->getArgument('baseuri');
        $targetPid = (int) $input->getArgument('pid');
        $storageId = (int) $input->getArgument('storageId');
        $storageFolder = $input->getArgument('storageFolder');
        $apiKey = $input->getArgument('apikey');
        $apiVersion = $input->getArgument('apiversion');
        if ($apiVersion == self::API_VERSION_ABOVE_227) {
            $importer = $this->getExtendedImporter();
            $success = $importer->import($baseUri, $apiKey, $targetPid, $storageId, $storageFolder);
            if (!$success) {
                $exceptions = $importer->getExceptions();
                /** @var \Exception[] $exceptions */
                if (!empty($exceptions)) {
                    $output->writeln(sprintf(
                        '<info>Exception occurred: "%s"</info>',
                        $exceptions[0]->getMessage()
                    ));
                }
            }
        } else {
            $importer = $this->getImporter();
            $success = $importer->import($baseUri, $apiKey, $targetPid, (int) $storageId, $storageFolder);
        }


        $this->callHooks($targetPid);

        if ($importer->hasChangedData()) {
            /** @var CacheUtility $cacheUtility */
            $cacheUtility = GeneralUtility::makeInstance(CacheUtility::class);
            $cacheUtility->clearCacheForPage($targetPid);
        }

        return $success ? 0 : 1;
    }

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\Importer
     */
    private function getImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\Importer $importer */
        $importer = GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\Importer::class);

        return $importer;
    }

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter
     */
    private function getExtendedImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter $importer */
        $importer = GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter::class);

        return $importer;
    }

    private function callHooks(int $targetPid)
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
