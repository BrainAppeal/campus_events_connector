<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\HasLastUpdateFieldDataTransformerInterface;

class EventDataTransformer extends DefaultDataTransformer implements HasLastUpdateFieldDataTransformerInterface
{
    protected function preProcessRawData(array $importData): ?array
    {
        $importData['seoRobotsIndex'] = ($importData['seoRobotsIndex'] ?? '') !== 'noindex';
        $importData['seoRobotsFollow'] = ($importData['seoRobotsFollow'] ?? '') !== 'nofollow';
        return $importData;
    }

    public function getLastUpdateTargetTcaField(): string
    {
        return 'modified_at_recursive';
    }
}
