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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

/**
 * The repository for ConverterConfigurations
 */
abstract class ConvertConfigurationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param null|int|int[] $pid
     */
    protected function setPidRestriction($pid)
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $defaultQuerySettings */
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
     * @param int $pid
     * @return object[]|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findActiveByPid($pid)
    {
        $this->setPidRestriction($pid);

        $query = $this->createQuery();
        $query->equals('hidden', '0');

        return $query->execute();
    }
}
