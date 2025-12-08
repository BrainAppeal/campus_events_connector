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

use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Sponsor
 */
class Sponsor extends AbstractImportedEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * url
     *
     * @var ?string
     */
    protected ?string $url = '';

    /**
     * imageHash
     *
     * @var ?string
     */
    protected ?string $imageHash = '';

    /**
     * Image
     * @var ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    #[Lazy]
    protected ?ObjectStorage $imageFile = null;

    public function __construct()
    {
        $this->imageFile ??= new ObjectStorage();
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
     * @return ?string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param ?string $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return ?string
     */
    public function getImageHash(): ?string
    {
        return $this->imageHash;
    }

    /**
     * @param ?string $imageHash
     */
    public function setImageHash(?string $imageHash): void
    {
        $this->imageHash = $imageHash;
    }

    /**
     * @return ObjectStorage
     */
    public function getImageFile(): ObjectStorage
    {
        if ($this->imageFile === null) {
            $this->imageFile = new ObjectStorage();
        }
        return $this->imageFile;
    }

    /**
     * @param ObjectStorage $imageFile
     */
    public function setImageFile(ObjectStorage $imageFile)
    {
        $this->imageFile = $imageFile;
    }
}
