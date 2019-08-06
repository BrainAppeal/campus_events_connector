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
 * TimeRange
 */
class TimeRange extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    /**
     * startDate
     *
     * @var \DateTime
     */
    protected $startDate = null;

    /**
     * startDateIsSet
     *
     * @var bool
     */
    protected $startDateIsSet;

    /**
     * endDate
     *
     * @var \DateTime
     */
    protected $endDate = null;

    /**
     * endDateIsSet
     *
     * @var bool
     */
    protected $endDateIsSet;

    /**
     * Returns the startDate
     *
     * @return \DateTime $startDate
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Sets the startDate
     *
     * @param \DateTime $startDate
     * @return void
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * Returns the endDate
     *
     * @return \DateTime $endDate
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Sets the endDate
     *
     * @param \DateTime $endDate
     * @return void
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return bool
     */
    public function isStartDateIsSet()
    {
        return $this->startDateIsSet;
    }

    /**
     * @param bool $startDateIsSet
     */
    public function setStartDateIsSet($startDateIsSet)
    {
        $this->startDateIsSet = $startDateIsSet;
    }

    /**
     * @return bool
     */
    public function isEndDateIsSet()
    {
        return $this->endDateIsSet;
    }

    /**
     * @param bool $endDateIsSet
     */
    public function setEndDateIsSet($endDateIsSet)
    {
        $this->endDateIsSet = $endDateIsSet;
    }
}
