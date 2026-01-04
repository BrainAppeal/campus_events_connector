<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\AfterRecordsWrittenEvent;
use Psr\Log\LoggerInterface;

readonly class LogImportProblemsEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected LoggerInterface        $logger
    )
    {
    }

    public function __invoke(AfterRecordsWrittenEvent $event): void
    {
        foreach ($this->dataTransformerFactory->getAll() as $dataTransformer) {
            $croppedFieldInfo = $dataTransformer->getCroppedFieldInfo();
            foreach ($croppedFieldInfo as $field => $error) {
                $errorMessage = sprintf(
                    'Field "%s" in table "%s" is too long. The longest value was %d characters long, but only %d characters are allowed.',
                    $field,
                    $dataTransformer->getTable(),
                    $error['maxLength'],
                    $error['allowedLength']
                );
                $this->logger->error($errorMessage, $error);
            }
        }
    }
}
