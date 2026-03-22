<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Utility;

use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\SlugHelper;

/**
 * A helper class that extends the functionality of SlugHelper for managing unique slugs
 * across different records within a table, considering language and workspace constraints.
 *
 * Since we usually check all records in the table for a given slug, this class caches the results
 * This vastly improves performance when importing large amounts of data
 */
class ImportSlugHelper extends SlugHelper
{
    protected static array $tableSlugs = [];

    /**
     * Check if there are other records with the same slug.
     */
    public function isUniqueInTable(string $slug, RecordState $state): bool
    {
        $languageId = $state->getContext()->getLanguageId();
        if (!isset(self::$tableSlugs[$this->tableName][$this->fieldName][$languageId])) {
            $queryBuilder = $this->createPreparedQueryBuilder();
            $this->applyLanguageConstraint($queryBuilder, $languageId);
            $this->applyWorkspaceConstraint($queryBuilder, $state);
            $statement = $queryBuilder->executeQuery();

            $records = $this->resolveVersionOverlays(
                $statement->fetchAllAssociative()
            );
            $mapSlugs = array_column($records, 'uid', $this->fieldName);
            self::$tableSlugs[$this->tableName][$this->fieldName][$languageId] = $mapSlugs;
        }
        $recordId = $state->getSubject()->getIdentifier();
        $matchUid = self::$tableSlugs[$this->tableName][$this->fieldName][$languageId][$slug] ?? null;
        return !$matchUid || (int)$matchUid === (int)$recordId;
    }

    public function setTableSlug(int $recordId, string $slug, int $languageId): void
    {
        self::$tableSlugs[$this->tableName][$this->fieldName][$languageId][$slug] = $recordId;
    }
}
