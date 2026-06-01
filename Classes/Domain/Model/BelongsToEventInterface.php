<?php

declare(strict_types=1);

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

/**
 * Indicates that the entity belongs to an event (i.e. has an event property)
 */
interface BelongsToEventInterface
{
    /**
     * @return Event|null
     */
    public function getEvent(): ?Event;

    /**
     * @param Event|null $event
     */
    public function setEvent(?Event $event): void;
}
