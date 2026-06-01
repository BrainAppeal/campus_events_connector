<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use BrainAppeal\CampusEventsConnector\Import\DataTransformer\DataTransformerFactory;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Class BrainAppeal\CampusEventsConnector\Import\Repository\ImportRecordReader
 *
 * Responsible for managing the retrieval, processing, and updating of import records
 * during various phases of an import workflow. This class interacts with import
 * tables, generates query builders, and ensures appropriate transformations and
 * updates are applied during the import cycle.
 */
readonly class ImportRecordReader extends AbstractImportRowRepository
{
    public const IMPORT_ROW_TYPE_UNMAPPED = 1;
    public const IMPORT_ROW_TYPE_MAPPED = 2;

    public function __construct(
        protected DataTransformerFactory $dataTransformerFactory,
        LoggerInterface $logger,
        ConnectionPool $connectionPool
    ) {
        parent::__construct($logger, $connectionPool);
    }

    /**
     * Finds and retrieves rows from the import row table that match specific conditions
     * related to the provided import entry.
     *
     * @param int $importId The import entry object used to filter the rows based on its unique identifier.
     * @param int $limit The maximum number of rows to retrieve. If set to 0, no limit is applied.
     * @param int $importRowType The type of import rows to retrieve (unmapped or mapped).
     * @return array<ImportRecordModel> An array of ImportRecordModel objects representing the matching rows
     *               with the required conditions.
     * @throws Exception
     */
    public function findImportRows(int $importId, int $limit, int $importRowType, int $languageId): array
    {
        $queryBuilder = $this->createQueryBuilderUnprocessedRows($importId, $importRowType);
        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }
        $queryBuilder->andWhere($queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId)));
        $queryBuilder->addOrderBy('priority', 'DESC');
        $queryBuilder->addOrderBy('target_record_uid', 'ASC');
        $queryBuilder->addOrderBy('source_record_uid', 'ASC');
        return $this->getImportRowsWithQueryBuilder($queryBuilder);
    }

    /**
     * Returns the row sums for the import row table based on the specified import entry by language id.
     *
     * @param int $importId The identifier of the import object that determines the scope
     *                      of the updated records to be evaluated.
     * @return array<array{sys_language_uid: int, sum_total_rows: int, sum_data_not_processed: int, sum_files_not_processed: int, sum_unmapped: int, sum_mapped: int}> The sums for the import row table
     */
    public function countRowsForProcessingByLanguage(int $importId): array
    {
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId);
        $queryBuilder->andWhere(
            // Only process records where the full data have been loaded
            $queryBuilder->expr()->eq('data_fully_loaded', $queryBuilder->createNamedParameter(
                1,
                Connection::PARAM_INT
            )),
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('data_processed', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                )),
                $queryBuilder->expr()->eq('files_processed', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                ))
            )
        );
        $queryBuilder->groupBy('sys_language_uid');
        $queryBuilder->orderBy('sys_language_uid', 'ASC');
        $queryBuilder
            ->selectLiteral(
                'sys_language_uid',
                'COUNT(*) AS sum_total_rows',
                'SUM(data_processed = 0) AS sum_data_not_processed',
                'SUM(files_processed = 0) AS sum_files_not_processed',
                'SUM(files_processed = 0) AS sum_files_not_processed',
                'SUM(target_record_uid = 0) AS sum_unmapped',
                'SUM(target_record_uid > 0) AS sum_mapped'
            );
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Creates a QueryBuilder instance configured for retrieving import rows to be processed based on the provided
     * import ID.
     *
     * @param int $importId The import object containing the ID and context for the current import operation.
     * @param int $importRowType The type of import rows to retrieve (unmapped or mapped).
     * @return QueryBuilder The configured QueryBuilder instance for the specified processing type.
     */
    protected function createQueryBuilderUnprocessedRows(int $importId, int $importRowType): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilderForImportRowTable($importId);
        $queryBuilder->andWhere(
            // Only process records where the full data have been loaded
            $queryBuilder->expr()->eq('data_fully_loaded', $queryBuilder->createNamedParameter(
                1,
                Connection::PARAM_INT
            ))
        );
        if ($importRowType === self::IMPORT_ROW_TYPE_UNMAPPED) {
            // Only rows with data_processed = 0 can be unmapped
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('data_processed', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                )),
                $queryBuilder->expr()->eq('target_record_uid', $queryBuilder->createNamedParameter(
                    0,
                    Connection::PARAM_INT
                ))
            );
            $queryBuilder->andWhere();
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('data_processed', $queryBuilder->createNamedParameter(
                        0,
                        Connection::PARAM_INT
                    )),
                    $queryBuilder->expr()->eq('files_processed', $queryBuilder->createNamedParameter(
                        0,
                        Connection::PARAM_INT
                    ))
                )
            );
            $queryBuilder->andWhere($queryBuilder->expr()->gt('target_record_uid', $queryBuilder->createNamedParameter(
                0,
                Connection::PARAM_INT
            )));
        }
        return $queryBuilder;
    }

    /**
     * Retrieves the total number of rows for the specified import ID.
     * @param int $importId
     * @return array{total_rows: int, full_loaded_rows: int, imported_rows: int, skipped_rows: int}|false
     */
    public function getRowCountsForImport(int $importId): array|false
    {
        $table = AbstractImportRowRepository::TABLE_IMPORT_ROW;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder
            ->selectLiteral(
                'COUNT(*) AS total_rows',
                'SUM(data_fully_loaded = 1) AS full_loaded_rows',
                'SUM(data_fully_loaded = 1 AND data_processed = 1 AND files_processed = 1) AS imported_rows',
                'SUM(import_skipped = 1) AS skipped_rows'
            )
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'import_id',
                    $queryBuilder->createNamedParameter($importId, Connection::PARAM_INT)
                )
            )
            ->groupBy('import_id');
        return $queryBuilder->executeQuery()->fetchAssociative();
    }
}
