<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */


namespace BrainAppeal\CampusEventsConnector\Domain\Model;


trait DatePeriodTrait
{

    /**
     * Start time stamp
     *
     * @var int
     */
    protected $startTstamp = 0;

    /**
     * End time stamp
     *
     * @var int
     */
    protected $endTstamp = 0;

    /**
     * @return int
     */
    public function getStartTstamp(): int
    {
        return $this->startTstamp;
    }

    /**
     * @param int $startTstamp
     */
    public function setStartTstamp(int $startTstamp): void
    {
        $this->startTstamp = $startTstamp;
    }

    /**
     * @return int
     */
    public function getEndTstamp(): int
    {
        return $this->endTstamp;
    }

    /**
     * @param int $endTstamp
     */
    public function setEndTstamp(int $endTstamp): void
    {
        $this->endTstamp = $endTstamp;
    }

    /**
     * Returns the startDate
     *
     * @return \DateTime $startDate
     */
    public function getStartDate()
    {
        $tstamp = $this->getStartTstamp();
        if (empty($tstamp)) {
            return null;
        }
        return date_create('@' . $tstamp);
    }

    /**
     * Sets the start date
     *
     * @param \DateTime $startDate
     * @return void
     * @deprecated Use setStartTstamp
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startTstamp = $startDate->getTimestamp();
    }

    /**
     * Returns the endDate
     *
     * @return \DateTime|null $endDate
     */
    public function getEndDate()
    {
        $tstamp = $this->getEndTstamp();
        if (empty($tstamp)) {
            return null;
        }
        return date_create('@' . $tstamp);
    }

    /**
     * Sets the end date
     *
     * @param \DateTime $endDate
     * @return void
     * @deprecated Use setEndTstamp
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endTstamp = $endDate->getTimestamp();
    }

}
