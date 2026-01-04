<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Event\PostProcessBatchEvent;
use BrainAppeal\CampusEventsConnector\Import\Exception\ImportOptionsConfigurationException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Writer\FileWriter;
use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

readonly class PostProcessFilesEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        protected FileWriter             $fileImport,
    ) {}

    public function __invoke(PostProcessBatchEvent $event): void
    {
        $importOptions = $event->getImportOptions();
        $processingResult = $event->getProcessingResult();
        if (!empty($processFilesList = $processingResult->getProcessFilesList())) {
            $fileTargetResourceIdentifier = $importOptions->getFileTargetResourceIdentifier();
            if (empty($fileTargetResourceIdentifier)) {
                throw new ImportOptionsConfigurationException(sprintf('The fileTargetResourceIdentifier option is required for file processing in %s', $event->getImportOptions()->getImportSource()));
            }
            $filesProcessedCount = $this->processFiles($processFilesList, $fileTargetResourceIdentifier);
            // Don't add the $filesProcessedCount to the total count if we are importing all data at once,
            // otherwise the records with files will be counted twice
            if ($filesProcessedCount > 0 && $importOptions->getLimit() > 0) {
                $processingResult->incrementProcessedRowCount($filesProcessedCount);
            }
        }
    }

    /**
     * Processes files for the given list of rows.
     *
     * @param array<string, array<ImportRecordModel>> $processFilesList Rows grouped by source type for which files need to be processed.
     * @param string $fileTargetResourceIdentifier
     * @return int
     * @throws Exception
     * @throws FolderDoesNotExistException
     */
    protected function processFiles(array $processFilesList, string $fileTargetResourceIdentifier): int
    {
        $updatedRowCount = 0;
        $fileImport = $this->fileImport;
        foreach ($processFilesList as $targetTable => $sourceRows) {
            $dataTransformer = $this->dataTransformerFactory->getDataTransformerByTable($targetTable);
            if ($dataTransformer->hasFileTransformations()) {
                $fileImport->processImportModelList($dataTransformer, $sourceRows, $fileTargetResourceIdentifier);
                ++$updatedRowCount;
            }
        }
        return $updatedRowCount;
    }
}
