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
     * @var ?Event
     */
    protected ?Event $event = null;

    /**
     * @var ?EventSession
     */
    protected ?EventSession $eventSession = null;

    /**
     * startDateIsSet
     *
     * @var ?bool
     */
    protected ?bool $startDateIsSet = false;

    /**
     * endDateIsSet
     *
     * @var ?bool
     */
    protected ?bool $endDateIsSet = false;

    /**
     * Visible until
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $visibleUntil = null;

    /**
     * @return ?Event
     */
    public function getEvent(): ?Event
    {
        return $this->eventSession?->getEvent();
    }

    /**
     * @param ?Event $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * @return ?EventSession
     */
    public function getEventSession(): ?EventSession
    {
        return $this->eventSession;
    }

    /**
     * @param ?EventSession $eventSession
     */
    public function setEventSession(?EventSession $eventSession): void
    {
        $this->eventSession = $eventSession;
        // TimeRange without an event session makes no sense, so the event is also unset
        if ($eventSession === null) {
            $this->event = null;
        } elseif (($event = $eventSession->getEvent()) && $event !== $this->event) {
            $this->event = $event;
        }
    }

    /**
     * @return bool
     */
    public function isStartDateIsSet(): bool
    {
        return (bool)$this->startDateIsSet;
    }

    /**
     * @param ?bool $startDateIsSet
     */
    public function setStartDateIsSet(?bool $startDateIsSet): void
    {
        $this->startDateIsSet = (bool)$startDateIsSet;
    }

    /**
     * @return bool
     */
    public function isEndDateIsSet(): bool
    {
        return (bool)$this->endDateIsSet;
    }

    /**
     * @param ?bool $endDateIsSet
     */
    public function setEndDateIsSet(?bool $endDateIsSet): void
    {
        $this->endDateIsSet = (bool)$endDateIsSet;
    }

    public function getVisibleUntil(): ?\DateTime
    {
        return $this->visibleUntil;
    }

    public function setVisibleUntil(?\DateTime $visibleUntil): void
    {
        $this->visibleUntil = $visibleUntil;
    }


}
