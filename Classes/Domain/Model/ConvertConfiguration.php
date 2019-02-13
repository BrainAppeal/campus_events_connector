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


namespace BrainAppeal\CampusEventsConnector\Domain\Model;

/**
 * ConvertConfiguration
 */
abstract class ConvertConfiguration extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
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
    protected $templatePath = null;

    /**
     * targetGroups
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup>
     */
    protected $targetGroups = null;

    /**
     * filterCategories
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory>
     */
    protected $filterCategories = null;

    public function __construct()
    {
        $this->filterCategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->targetGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
     * @return void
     */
    public function setTargetPid($targetPid)
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
     * @return void
     */
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Adds a TargetGroup
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroup
     * @return void
     */
    public function addTargetGroup(\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroup)
    {
        $this->targetGroups->attach($targetGroup);
    }

    /**
     * Removes a TargetGroup
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove The TargetGroup to be removed
     * @return void
     */
    public function removeTargetGroup(\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove)
    {
        $this->targetGroups->detach($targetGroupToRemove);
    }

    /**
     * Returns the targetGroups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> targetGroups
     */
    public function getTargetGroups()
    {
        return $this->targetGroups;
    }

    /**
     * Sets the targetGroups
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> $targetGroups
     * @return void
     */
    public function setTargetGroups(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups)
    {
        $this->targetGroups = $targetGroups;
    }

    /**
     * Adds a FilterCategory
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategory
     * @return void
     */
    public function addFilterCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategory)
    {
        $this->filterCategories->attach($filterCategory);
    }

    /**
     * Removes a FilterCategory
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove The FilterCategory to be removed
     * @return void
     */
    public function removeFilterCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove)
    {
        $this->filterCategories->detach($filterCategoryToRemove);
    }

    /**
     * Returns the filterCategories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> filterCategories
     */
    public function getFilterCategories()
    {
        return $this->filterCategories;
    }

    /**
     * Sets the filterCategories
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> $filterCategories
     * @return void
     */
    public function setFilterCategories(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $filterCategories)
    {
        $this->filterCategories = $filterCategories;
    }
}
