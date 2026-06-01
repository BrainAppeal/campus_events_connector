<?php

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * EventSession
 */
class EventSession extends AbstractImportedEntity implements BelongsToEventInterface
{
    use DatePeriodTrait;

    /**
     * @var ?Event
     */
    protected $event;

    /**
     * session time periods
     *
     * @var ObjectStorage<TimeRange>
     */
    protected $sessionTimePeriods;

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
     */
    protected function initStorageObjects()
    {
        $this->sessionTimePeriods = new ObjectStorage();
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
     * @return ObjectStorage<TimeRange>
     */
    public function getSessionTimePeriods()
    {
        return $this->sessionTimePeriods;
    }

    /**
     * @param ObjectStorage<TimeRange> $sessionTimePeriods
     */
    public function setSessionTimePeriods($sessionTimePeriods): void
    {
        $this->sessionTimePeriods = $sessionTimePeriods;
    }

    /**
     * Adds a SessionTimePeriod
     *
     * @param TimeRange $sessionTimePeriod
     */
    public function addSessionTimePeriod(TimeRange $sessionTimePeriod): void
    {
        if (($event = $this->getEvent()) instanceof Event) {
            $sessionTimePeriod->setEvent($event);
        }
        $this->getSessionTimePeriods()->attach($sessionTimePeriod);
    }

    /**
     * Removes a SessionTimePeriod
     *
     * @param TimeRange $sessionTimePeriodToRemove The SessionTimePeriod to be removed
     */
    public function removeSessionTimePeriod(TimeRange $sessionTimePeriodToRemove): void
    {
        $this->getSessionTimePeriods()->detach($sessionTimePeriodToRemove);
    }
}
