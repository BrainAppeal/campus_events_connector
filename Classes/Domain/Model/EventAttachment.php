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
 * EventAttachment
 */
class EventAttachment extends AbstractImportedEntity implements BelongsToEventInterface
{

    /**
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\Event
     */
    protected $event;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * fileHash
     *
     * @var string
     */
    protected $fileHash = '';

    /**
     * Image
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $attachmentFile;

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
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getFileHash()
    {
        return $this->fileHash;
    }

    /**
     * @param string $fileHash
     */
    public function setFileHash($fileHash)
    {
        $this->fileHash = $fileHash;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function getAttachmentFile()
    {
        return $this->attachmentFile;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function setAttachmentFile($attachmentFile)
    {
        $this->attachmentFile = $attachmentFile;
    }
}
