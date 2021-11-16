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
     * @return void
     */
    public function setTitle($title)
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
     * @return void
     */
    public function setFirstName($firstName)
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
     * @return void
     */
    public function setLastName($lastName)
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
    public function setExternalUrl($externalUrl)
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
    public function setAcademicDegree($academicDegree)
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
    public function setInstitution($institution)
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
    public function setPhone($phone)
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
    public function setEmail($email)
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
    public function setBusinessAddress($businessAddress)
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
    public function setPublications($publications)
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
    public function setFocusOfWork($focusOfWork)
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
    public function setEventFormats($eventFormats)
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
    public function setReferences($references)
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
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
