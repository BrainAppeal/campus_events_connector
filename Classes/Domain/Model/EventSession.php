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

/**
 * EventSession
 */
class EventSession extends AbstractImportedEntity implements BelongsToEventInterface
{
    use DatePeriodTrait;

    /**
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\Event
     */
    protected $event = null;

    /**
     * session time periods
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange>
     */
    protected $sessionTimePeriods = null;

    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->sessionTimePeriods = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @return Event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @param Event|null $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getSessionTimePeriods()
    {
        return $this->sessionTimePeriods;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $sessionTimePeriods
     */
    public function setSessionTimePeriods($sessionTimePeriods)
    {
        $this->sessionTimePeriods = $sessionTimePeriods;
    }

    /**
     * Adds a SessionTimePeriod
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $organizer
     * @return void
     */
    public function addSessionTimePeriod(\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $sessionTimePeriod)
    {
        if (null !== $event = $this->getEvent()) {
            $sessionTimePeriod->setEvent($event);
        }
        $this->getSessionTimePeriods()->attach($sessionTimePeriod);
    }

    /**
     * Removes a SessionTimePeriod
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $sessionTimePeriodToRemove The SessionTimePeriod to be removed
     * @return void
     */
    public function removeSessionTimePeriod(\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $sessionTimePeriodToRemove)
    {
        $this->getSessionTimePeriods()->detach($sessionTimePeriodToRemove);
    }
}
