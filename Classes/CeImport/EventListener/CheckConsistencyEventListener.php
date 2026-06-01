<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\EventListener;

use BrainAppeal\CampusEventsConnector\CeImport\Model\ImportOptions;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\BeforeImportStartedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * The CheckConsistencyEventListener class is a read-only event listener responsible for ensuring
 * proper consistency of import source names during the import initialization phase.
 *
 * This class is triggered before the import process starts and operates by applying various
 * data transformations to align import source names with the target configuration.
 *
 * An event with import options is processed to retrieve the applicable data transformers,
 * which are then updated to ensure correct import source naming conventions.
 *
 * Constructor Dependencies:
 * - DataTransformerFactory: Used to retrieve the relevant data transformers based on the import group.
 * - ConnectionPool: Handles database connections to apply consistency updates to the corresponding tables.
 *
 * Methods:
 * - __invoke(BeforeImportStartedEvent $event): Handles the event that initiates the consistency check. Responsible
 *   for iterating over the applicable data transformers and invoking the adjustments required.
 * - fixImportSourceNames(ImportDataTransformerInterface $dataTransformer, ImportOptions $importOptions): A helper
 *   method to execute the changes related to the import source naming. It updates the database records based
 *   on the provided import options and the data transformer's configuration.
 */
#[AsEventListener(
    identifier: 'ce/before-start/fix-import-source-names'
)]
readonly class CheckConsistencyEventListener
{
    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function __invoke(BeforeImportStartedEvent $event): void
    {
        $importOptions = $event->getImportOptions();
        if ($importOptions instanceof ImportOptions) {
            foreach ($this->dataTransformerFactory->getDataTransformersForImportGroup($importOptions->getImportSource()) as $dataTransformer) {
                if ($dataTransformer->hasFileTransformations()) {
                    $dataTransformer->getFileDataTransformerHelper()->setBaseUri($importOptions->getBaseUri());
                }
                $this->fixImportSourceNames($dataTransformer, $importOptions);
            }
        }
    }

    /**
     * Updates the import source names in the database table based on the provided import options
     * and data transformer configuration.
     *
     * @param ImportDataTransformerInterface $dataTransformer The data transformer providing the
     *        import configuration and relevant settings.
     * @param ImportOptions $importOptions The options used to determine the target import source
     *        and base URI for the update operation.
     *
     * @return void
     */
    private function fixImportSourceNames(ImportDataTransformerInterface $dataTransformer, ImportOptions $importOptions): void
    {
        $targetSourceValue = $importOptions->getTargetImportSource();
        $importConfiguration = $dataTransformer->getImportConfiguration();
        $targetSourceField = $importConfiguration->getTargetImportSourceField();
        $baseUri = $importOptions->getBaseUri();
        $connectionPool = $this->connectionPool;
        if ($targetSourceField && $targetSourceValue) {
            $tableName = $importConfiguration->getTableName();
            $connection = $connectionPool->getConnectionForTable($tableName);
            $updateImportSourceSql = "UPDATE $tableName SET ce_import_source = ? WHERE (ce_import_source = ? OR (ce_import_id > 0 AND ce_import_source IS NULL))";
            $connection->executeStatement($updateImportSourceSql, [$targetSourceValue, $baseUri]);
        }
    }
}
