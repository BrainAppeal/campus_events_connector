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

/**
 * The repository for Events
 */
class EventRepository extends AbstractImportedRepository
{
    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration $configuration
     * @param int|bool|null $restrictToPid Restrict results to page UID; NULL := $configuration->getPid(); false := no restriction
     * @return array|\TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllByConvertConfiguration($configuration, $restrictToPid = null)
    {
        if (false !== $restrictToPid) {
            $pid = $restrictToPid ?? $configuration->getPid();
            $this->setPidRestriction($pid);
        } else {
            $this->setPidRestriction(null);
        }
        $query = $this->createQuery();
        $filterCategories = $configuration->getFilterCategories();
        $targetGroups = $configuration->getTargetGroups();
        $filterCategoryConstraints = [];
        $targetGroupConstraints = [];

        foreach ($filterCategories as $filterCategory) {
            $filterCategoryConstraints[] = $query->contains('filterCategories', $filterCategory);
        }
        foreach ($targetGroups as $targetGroup) {
            $targetGroupConstraints[] = $query->contains('targetGroups', $targetGroup);
        }
        if ($filterCategoryConstraints) {
            $query->matching($query->logicalAnd($filterCategoryConstraints));
        }
        if ($targetGroupConstraints) {
            $query->matching($query->logicalAnd($targetGroupConstraints));
        }
        $viewLists = $configuration->getViewLists();
        $viewListConstraints = [];
        foreach ($viewLists as $viewList) {
            $viewListConstraints[] = $query->contains('viewLists', $viewList);
        }
        if ($viewListConstraints) {
            $query->matching($query->logicalAnd($viewListConstraints));
        }
        return $query->execute();
    }
}
