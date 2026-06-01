<?php

declare(strict_types=1);

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

/**
 * Referent
 */
class Referent extends AbstractImportedEntity
{
    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * firstName
     *
     * @var string
     */
    protected $firstName = '';

    /**
     * lastName
     *
     * @var string
     */
    protected $lastName = '';

    /**
     * externalUrl
     *
     * @var string
     */
    protected $externalUrl = '';

    /**
     * academicDegree
     *
     * @var string
     */
    protected $academicDegree = '';

    /**
     * institution
     *
     * @var string
     */
    protected $institution = '';

    /**
     * phone
     *
     * @var string
     */
    protected $phone = '';

    /**
     * email
     *
     * @var string
     */
    protected $email = '';

    /**
     * businessAddress
     *
     * @var string
     */
    protected $businessAddress = '';

    /**
     * publications
     *
     * @var string
     */
    protected $publications = '';

    /**
     * focusOfWork
     *
     * @var string
     */
    protected $focusOfWork = '';

    /**
     * eventFormats
     *
     * @var string
     */
    protected $eventFormats = '';

    /**
     * references
     *
     * @var string
     */
    protected $references = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

    /**
     * type (internal/external)
     *
     * @var ?int
     */
    protected ?int $type = null;

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Sets the firstName
     *
     * @param string $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * Returns the lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Sets the lastName
     *
     * @param string $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    /**
     * @param string $externalUrl
     */
    public function setExternalUrl($externalUrl): void
    {
        $this->externalUrl = $externalUrl;
    }

    /**
     * @return string
     */
    public function getAcademicDegree()
    {
        return $this->academicDegree;
    }

    /**
     * @param string $academicDegree
     */
    public function setAcademicDegree($academicDegree): void
    {
        $this->academicDegree = $academicDegree;
    }

    /**
     * @return string
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @param string $institution
     */
    public function setInstitution($institution): void
    {
        $this->institution = $institution;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getBusinessAddress()
    {
        return $this->businessAddress;
    }

    /**
     * @param string $businessAddress
     */
    public function setBusinessAddress($businessAddress): void
    {
        $this->businessAddress = $businessAddress;
    }

    /**
     * @return string
     */
    public function getPublications()
    {
        return $this->publications;
    }

    /**
     * @param string $publications
     */
    public function setPublications($publications): void
    {
        $this->publications = $publications;
    }

    /**
     * @return string
     */
    public function getFocusOfWork()
    {
        return $this->focusOfWork;
    }

    /**
     * @param string $focusOfWork
     */
    public function setFocusOfWork($focusOfWork): void
    {
        $this->focusOfWork = $focusOfWork;
    }

    /**
     * @return string
     */
    public function getEventFormats()
    {
        return $this->eventFormats;
    }

    /**
     * @param string $eventFormats
     */
    public function setEventFormats($eventFormats): void
    {
        $this->eventFormats = $eventFormats;
    }

    /**
     * @return string
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @param string $references
     */
    public function setReferences($references): void
    {
        $this->references = $references;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): void
    {
        $this->type = (int)$type;
    }

}
