<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Workflow;

use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;

class ImportContextRegistry
{
    /**
     * @var array<string, ImportContext>
     */
    private array $contexts = [];

    public function create(AbstractImportOptions $options): ImportContext
    {
        $importSource = $options->getImportSource();
        if (isset($this->contexts[$importSource])) {
            throw new \RuntimeException("The import for this source has already been registered: $importSource");
        }
        $this->contexts[$importSource] = new ImportContext($options);
        return $this->contexts[$importSource];
    }

    public function has(string $importSource): bool
    {
        return isset($this->contexts[$importSource]);
    }

    public function get(string $importSource): ImportContext
    {
        if (!isset($this->contexts[$importSource])) {
            throw new \RuntimeException("No import context for ID: $importSource");
        }
        return $this->contexts[$importSource];
    }

    public function destroy(string $importSource): void
    {
        unset($this->contexts[$importSource]);
    }
}
