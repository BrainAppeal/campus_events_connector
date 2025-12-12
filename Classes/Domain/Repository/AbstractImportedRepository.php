<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Domain\Repository;

use BrainAppeal\CampusEventsConnector\Domain\Model\AbstractImportedEntity;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class AbstractImportedRepository
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.brain-appeal.com/
 * @since     2019-02-13
 *
 * @template T of AbstractImportedEntity
 * @extends Repository<AbstractImportedEntity>
 */
abstract class AbstractImportedRepository extends Repository
{
    /**
     * @var string[]
     */
    private static array $classTableMapping = [];

    public static function getTableForModelClass($modelClass): string
    {
        if (!isset(self::$classTableMapping[$modelClass])) {
            $dataMapper = GeneralUtility::makeInstance(DataMapFactory::class);
            self::$classTableMapping[$modelClass] = $dataMapper->buildDataMap($modelClass)->getTableName();
        }

        return self::$classTableMapping[$modelClass];
    }

    /**
     * @param null|int|int[] $pid
     */
    protected function setPidRestriction($pid): void
    {
        /** @var Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        if (null === $pid) {
            $defaultQuerySettings->setRespectStoragePage(false);
        } else {
            if (!is_array($pid)) {
                $pid = [$pid];
            }
            $defaultQuerySettings->setStoragePageIds($pid);
        }
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * Find all events on the given pid or pid list
     *
     * @param null|int|int[] $pid
     *
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllByPid($pid)
    {
        return $this->findListByPid($pid, [], 0);
    }

    /**
     * Find all events on the given pid or pid list
     * @param null|int|int[] $pid
     * @param array $constraints Optional query constraints
     * @param int $limit
     * @return QueryResultInterface|list<array<string,mixed>> The query result object or an array if $returnRawQueryResult is TRUE
     */
    public function findListByPid($pid, array $constraints = [], int $limit = 0)
    {
        $this->setPidRestriction($pid);
        $query = $this->createQuery();
        if (!empty($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }
        if ($limit > 0) {
            $query->setLimit($limit);
        }
        return $query->execute();
    }

    /**
     * @param string $importSource
     * @param int $importId
     * @param null|int|int[] $pid
     * @return ImportedModelInterface|null
     */
    public function findByImport(string $importSource, int $importId, $pid = null): ?ImportedModelInterface
    {
        $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($importSource);
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $query->matching($query->logicalAnd(
            $query->like('ceImportSource', $dbImportSource),
            $query->equals('ceImportId', $importId)
        ));
        $query->setOrderings([
            "ceImportedAt" => QueryInterface::ORDER_DESCENDING
        ]);

        $result = $query->execute()->getFirst();
        /** @var ?ImportedModelInterface $result */

        return $result;
    }

    /**
     * @param int $importId
     * @param null|int|int[] $pid
     * @return ImportedModelInterface|null
     */
    public function findByImportId(int $importId, $pid = null): ?ImportedModelInterface
    {
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $constraints = [
            $query->equals('ceImportId', $importId),
        ];
        $query->matching($query->logicalAnd(...$constraints));
        $query->setOrderings([
            "ceImportedAt" => QueryInterface::ORDER_DESCENDING
        ]);

        /** @var ImportedModelInterface $result */
        $result = $query->execute()->getFirst();

        return $result;
    }

    /**
     * @param string $importSource
     * @param int $importId
     * @param int $pid
     * @return ImportedModelInterface
     */
    public function createNewModelInstance(string $importSource, int $importId, int $pid): ImportedModelInterface
    {
        $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($importSource);
        /** @var ImportedModelInterface $object */
        $object = GeneralUtility::makeInstance($this->objectType);
        $object->setCeImportId($importId);
        $object->setCeImportSource($dbImportSource);
        $object->setPid($pid);

        return $object;
    }

    /**
     * Returns the table name for the current object
     *
     * @return string
     */
    public function getImportTableName(): string
    {
        return self::getTableForModelClass($this->objectType);
    }

    /**
     * @param int $timestamp
     * @param string $importSource
     * @param null|int|int[] $pid
     * @return array|AbstractImportedEntity[]
     */
    public function findByNotImportedSince(int $timestamp, string $importSource, $pid = null): array
    {
        $dbImportSource = DBALFactory::getInstance()->getFilteredDbImportSource($importSource);
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $query->matching($query->logicalAnd(
            $query->like('ceImportSource', $dbImportSource),
            $query->lessThan('ceImportedAt', $timestamp)
        ));

        $result = $query->execute()->toArray();
        /** @var AbstractImportedEntity[] $result */
        return $result;
    }

    public function persistAll(): void
    {
        $this->persistenceManager->persistAll();
    }
}
