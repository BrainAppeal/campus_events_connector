<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\BeforeImportStartedEvent;
use BrainAppeal\CampusEventsConnector\Import\Writer\FileWriter;

/**
 * A readonly event listener that checks the consistency of data transformations
 * before an import process starts. It ensures that the target file resource
 * used during the import process is consistent with the specifications of the
 * available data transformers. If inconsistencies are detected, the import
 * process is updated to run in force update mode.
 */
readonly class CheckConsistencyEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected FileWriter             $fileImport,
    ) {}

    public function __invoke(BeforeImportStartedEvent $event): void
    {
        $importOptions = $event->getImportOptions();
        $fileTargetResourceIdentifier = $importOptions->getFileTargetResourceIdentifier();
        if ($fileTargetResourceIdentifier) {
            $isConsistent = true;
            foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
                if ($dataTransformer->hasFileTransformations()) {
                    if (!$this->fileImport->checkConsistency($dataTransformer->getTable(), $fileTargetResourceIdentifier)) {
                        $isConsistent = false;
                    }
                }
            }
            if (!$isConsistent) {
                $importOptions->enableForceUpdate();
            }
        }
    }
}
