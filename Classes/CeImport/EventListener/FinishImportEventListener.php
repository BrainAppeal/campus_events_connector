<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\EventListener;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\DataTransformer\ImportDataTransformerInterface;
use BrainAppeal\CampusEventsConnector\Import\Event\ImportFinishEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[AsEventListener(
    identifier: 'ce/import/finish-set-imported-at'
)]
readonly class FinishImportEventListener
{
    public function __construct(
        private DataTransformerFactory $dataTransformerFactory,
        private ConnectionPool $connectionPool
    ) {}

    public function __invoke(ImportFinishEvent $event): void
    {
        $context = $event->getContext();
        foreach ($this->dataTransformerFactory->getDataTransformersByContext($context) as $dataTransformer) {
            $this->updateImportedAtField($dataTransformer);
        }
    }

    /**
     * Update the imported_at field for the data transformer's table if the field exists.
     * This is necessary to ensure that the imported_at field is set for all records,
     * even if the import process was interrupted before the field could be set during the import.
     */
    private function updateImportedAtField(ImportDataTransformerInterface $dataTransformer): void
    {
        $connectionPool = $this->connectionPool;
        $tableName = $dataTransformer->getTable();
        $connection = $connectionPool->getConnectionForTable($tableName);
        $schemaManager = $connection->createSchemaManager();
        $tableColumInfo = $schemaManager->listTableColumns($tableName);
        foreach ($tableColumInfo as $column) {
            if ($column->getName() === 'ce_imported_at') {
                $updateImportSourceSql = "UPDATE $tableName SET ce_imported_at = tstamp WHERE ce_import_id > 0 AND (ce_imported_at IS NULL OR ce_imported_at < tstamp)";
                $connection->executeStatement($updateImportSourceSql);
                break;
            }
        }
    }
}
