<?php

namespace BrainAppeal\CampusEventsConnector\Domain\Repository;

/***
 *
 * This file is part of the "BrainAppeal CampusEvents Connector" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Joshua Billert <joshua.billert@brain-appeal.com>, Brain Appeal GmbH
 *
 ***/

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;

/**
 * Class AbstractImportedRepository
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-03
 */
abstract class AbstractImportedRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param null|int|int[] $pid
     */
    protected function setPidRestriction($pid)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
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
     * Find all events on given pid
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
     * Find all events on given pid
     * @param null|int|int[] $pid
     * @param array $constraints Optional query constraints
     * @param int $limit
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findListByPid($pid, $constraints = [], $limit = 0)
    {
        $this->setPidRestriction($pid);
        $query = $this->createQuery();
        if (!empty($constraints)) {
            $query->matching($query->logicalAnd($constraints));
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
    public function findByImport($importSource, $importId, $pid = null)
    {
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $constraints = [
            $query->like('importSource', $importSource),
            $query->equals('importId', $importId),
        ];
        $query->matching($query->logicalAnd($constraints));
        $query->setOrderings([
            "importedAt" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);

        /** @var ImportedModelInterface $result */
        $result = $query->execute()->getFirst();

        return $result;
    }

    /**
     * @param string $importSource
     * @param int $importId
     * @param null|int|int[] $pid
     * @return ImportedModelInterface
     */
    public function findByImportOrCreate($importSource, $importId, $pid = null)
    {
        $object = $this->findByImport($importSource, $importId, $pid);

        if (null === $object) {
            /** @var ImportedModelInterface $object */
            $object = new $this->objectType;
            $object->setImportId($importId);
            $object->setImportSource($importSource);
            $object->setPid(intval($pid));
        }

        return $object;
    }

    /**
     * @param int $timestamp
     * @param string $importSource
     * @param null|int|int[] $pid
     * @return ImportedModelInterface[]
     */
    public function findByNotImportedSince($timestamp, $importSource, $pid = null)
    {
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $constraints = [
            $query->like('importSource', $importSource),
            $query->lessThan('importedAt', $timestamp),
        ];
        $query->matching($query->logicalAnd($constraints));

        $result = $query->execute();

        return $result->toArray();
    }

    public function persistAll()
    {
        $this->persistenceManager->persistAll();
    }
}
