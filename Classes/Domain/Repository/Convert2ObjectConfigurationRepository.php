<?php

namespace BrainAppeal\CampusEventsConnector\Domain\Repository;

/***
 *
 * This file is part of the "CampusEventsConvert2News" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018
 *
 ***/

/**
 * The repository for ConverterConfigurations
 */
abstract class Convert2ObjectConfigurationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param null|int|int[] $pid
     */
    protected function setPidRestriction($pid)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $defaultQuerySettings */
        $defaultQuerySettings = $this->objectManager->get(
            \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class
        );
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

    public function findActiveByPid($pid)
    {
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $query->equals('hidden', '0');

        return $query->execute();
    }
}
