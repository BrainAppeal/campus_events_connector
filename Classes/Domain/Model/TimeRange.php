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
class TimeRange extends AbstractImportedEntity implements BelongsToEventInterface
{
    use DatePeriodTrait;

    /**
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\Event
     */
    protected $event = null;

    /**
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\EventSession
     */
    protected $eventSession = null;

    /**
     * startDateIsSet
     *
     * @var bool
     */
    protected $startDateIsSet;

    /**
     * endDateIsSet
     *
     * @var bool
     */
    protected $endDateIsSet;

    /**
     * @return Event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return EventSession
     */
    public function getEventSession(): ?EventSession
    {
        return $this->eventSession;
    }

    /**
     * @param EventSession $eventSession
     */
    public function setEventSession(?EventSession $eventSession): void
    {
        $this->eventSession = $eventSession;
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
