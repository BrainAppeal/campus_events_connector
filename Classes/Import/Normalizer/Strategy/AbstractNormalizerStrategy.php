<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Utility\DataParser;

/**
 * Abstract strategy for normalizing values.
 */
abstract readonly class AbstractNormalizerStrategy implements NormalizerStrategyInterface
{
    protected bool $isNullable;
    protected DataParser $parser;

    public function __construct(ImportFieldConfigurationModel $mapEntry)
    {
        $this->parser = new DataParser();
        $this->isNullable = $mapEntry->isNullable();
    }
}
