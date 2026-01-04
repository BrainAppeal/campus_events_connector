<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

/**
 * Interface for data transformers that specify a target field for the last update information.
 *
 * This interface extends the ImportDataTransformerInterface and provides
 * a method to obtain the name of the target field where the last update data
 * should be assigned.
 */
interface HasLastUpdateFieldDataTransformerInterface extends ImportDataTransformerInterface
{
    public function getLastUpdateTargetTcaField(): string;
}
