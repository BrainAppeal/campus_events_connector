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
     */
    public function findAllByConvertConfiguration($configuration)
    {
        $this->setPidRestriction($configuration->getPid());
        $query = $this->createQuery();
        $filterCategories = $configuration->getFilterCategories();
        $targetGroups = $configuration->getTargetGroups();
        $filterCategoryConstraints = [];
        $targetGroupConstraints = [];

        foreach ($filterCategories as $filterCategory) {
            $filterCategoryConstraints = $query->contains('filter_categories', $filterCategory);
        }
        foreach ($targetGroups as $targetGroup) {
            $targetGroupConstraints = $query->contains('target_groups', $targetGroup);
        }
        if ($filterCategoryConstraints) {
            $query->logicalAnd($filterCategoryConstraints);
        }
        if ($targetGroupConstraints) {
            $query->logicalAnd($targetGroupConstraints);
        }
        return $query->execute();
    }
}
