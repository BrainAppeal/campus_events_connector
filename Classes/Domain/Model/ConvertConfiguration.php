<?php

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * ConvertConfiguration
 */
abstract class ConvertConfiguration extends AbstractEntity
{
    /**
     * targetPid
     *
     * @var int
     */
    protected $targetPid = 0;

    /**
     * templatePath
     *
     * @var string
     */
    protected $templatePath;

    /**
     * targetGroups
     *
     * @var ObjectStorage<TargetGroup>
     */
    protected $targetGroups;

    /**
     * viewLists
     *
     * @var ObjectStorage<ViewList>
     */
    protected $viewLists;

    /**
     * filterCategories
     *
     * @var ObjectStorage<FilterCategory>
     */
    protected $filterCategories;

    public function __construct()
    {
        $this->filterCategories = new ObjectStorage();
        $this->targetGroups = new ObjectStorage();
        $this->viewLists = new ObjectStorage();
    }

    /**
     * Returns the targetPid
     *
     * @return int $targetPid
     */
    public function getTargetPid()
    {
        return $this->targetPid;
    }

    /**
     * Sets the targetPid
     *
     * @param int $targetPid
     */
    public function setTargetPid($targetPid): void
    {
        $this->targetPid = $targetPid;
    }

    /**
     * Returns the templatePath
     *
     * @return string $templatePath
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * Sets the templatePath
     *
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Adds a TargetGroup
     *
     * @param TargetGroup $targetGroup
     */
    public function addTargetGroup(TargetGroup $targetGroup): void
    {
        $this->targetGroups->attach($targetGroup);
    }

    /**
     * Removes a TargetGroup
     *
     * @param TargetGroup $targetGroupToRemove The TargetGroup to be removed
     */
    public function removeTargetGroup(TargetGroup $targetGroupToRemove): void
    {
        $this->targetGroups->detach($targetGroupToRemove);
    }

    /**
     * Returns the targetGroups
     *
     * @return ObjectStorage<TargetGroup> targetGroups
     */
    public function getTargetGroups()
    {
        return $this->targetGroups;
    }

    /**
     * Sets the targetGroups
     *
     * @param ObjectStorage<TargetGroup> $targetGroups
     */
    public function setTargetGroups(ObjectStorage $targetGroups): void
    {
        $this->targetGroups = $targetGroups;
    }

    /**
     * Adds a FilterCategory
     *
     * @param FilterCategory $filterCategory
     */
    public function addFilterCategory(FilterCategory $filterCategory): void
    {
        $this->filterCategories->attach($filterCategory);
    }

    /**
     * Removes a FilterCategory
     *
     * @param FilterCategory $filterCategoryToRemove The FilterCategory to be removed
     */
    public function removeFilterCategory(FilterCategory $filterCategoryToRemove): void
    {
        $this->filterCategories->detach($filterCategoryToRemove);
    }

    /**
     * Returns the filterCategories
     *
     * @return ObjectStorage<FilterCategory> filterCategories
     */
    public function getFilterCategories()
    {
        return $this->filterCategories;
    }

    /**
     * Sets the filterCategories
     *
     * @param ObjectStorage<FilterCategory> $filterCategories
     */
    public function setFilterCategories(ObjectStorage $filterCategories): void
    {
        $this->filterCategories = $filterCategories;
    }

    /**
     * Adds a ViewList
     *
     * @param ViewList $viewList
     */
    public function addViewList(ViewList $viewList): void
    {
        $this->getViewLists()->attach($viewList);
    }

    /**
     * Removes a ViewList
     *
     * @param ViewList $viewListToRemove The ViewList to be removed
     */
    public function removeViewList(ViewList $viewListToRemove): void
    {
        $this->getViewLists()->detach($viewListToRemove);
    }

    /**
     * Returns the viewLists
     *
     * @return ObjectStorage<ViewList> viewLists
     */
    public function getViewLists()
    {
        if ($this->viewLists === null) {
            $this->viewLists = new ObjectStorage();
        }
        return $this->viewLists;
    }

    /**
     * Sets the viewLists
     *
     * @param ObjectStorage<ViewList> $viewLists
     */
    public function setViewLists(ObjectStorage $viewLists): void
    {
        $this->viewLists = $viewLists;
    }
}
