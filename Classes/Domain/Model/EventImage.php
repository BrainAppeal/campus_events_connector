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

use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * EventImage
 */
class EventImage extends AbstractImportedEntity implements BelongsToEventInterface
{
    /**
     * @var ?Event
     */
    protected $event;

    /**
     * name
     *
     * @var ?string
     */
    protected $name = '';

    /**
     * fileHash
     *
     * @var ?string
     */
    protected $fileHash = '';

    /**
     * Image
     * @var ?FileReference
     */
    protected $imageFile;

    /**
     * @return ?Event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @param ?Event $event
     */
    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    /**
     * Returns the name
     *
     * @return ?string $name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param ?string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ?string
     */
    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    /**
     * @param ?string $fileHash
     */
    public function setFileHash(?string $fileHash): void
    {
        $this->fileHash = $fileHash;
    }

    /**
     * @return ?FileReference
     */
    public function getImageFile(): ?FileReference
    {
        return $this->imageFile;
    }

    /**
     * @param ?FileReference $imageFile
     */
    public function setImageFile(?FileReference $imageFile): void
    {
        $this->imageFile = $imageFile;
    }
}
