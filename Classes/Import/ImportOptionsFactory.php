<?php

namespace BrainAppeal\CampusEventsConnector\Import;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Factory class for managing an instance of AbstractImportOptions.
 * Implements the SingletonInterface to ensure a single instance of import options is managed.
 */
class ImportOptionsFactory implements SingletonInterface
{
    private ?AbstractImportOptions $importOptions = null;

    public function get(): AbstractImportOptions
    {
        if ($this->importOptions === null) {
            throw new \RuntimeException('ImportOptionsFactory::get() called before ImportOptionsFactory::set()');
        }

        return $this->importOptions;
    }

    public function set(AbstractImportOptions $importOptions): void
    {
        $this->importOptions = $importOptions;
    }
}
