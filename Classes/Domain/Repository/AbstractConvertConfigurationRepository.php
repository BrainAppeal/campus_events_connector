<?php

namespace BrainAppeal\BrainEventConnector\Domain\Repository;

/***
 *
 * This file is part of the "BrainEventConvert2News" Extension for TYPO3 CMS.
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
abstract class AbstractConvertConfigurationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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

    public function findAllByPid($pid)
    {
        $this->setPidRestriction($pid);

        return $this->findAll();
    }
}
