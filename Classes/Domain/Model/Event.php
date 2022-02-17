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
 * Events
 */
class Event extends AbstractImportedEntity
{
    use DatePeriodTrait;

    /**
     * status
     *
     * @var int
     * @deprecated
     */
    protected $status = 0;

    /**
     * orderType
     *
     * @var int
     */
    protected $orderType = 0;

    /**
     * canceled
     *
     * @var bool
     * @deprecated
     */
    protected $canceled = false;

    /**
     * url
     *
     * @var string
     */
    protected $url = '';

    /**
     * External order url
     *
     * @var string
     */
    protected $externalOrderUrl = '';

    /**
     * External order email address
     *
     * @var string
     */
    protected $externalOrderEmailAddress = '';

    /**
     * Direct registration url
     *
     * @var string
     */
    protected $directRegistrationUrl = '';

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * subtitle
     *
     * @var string
     */
    protected $subtitle = '';

    /**
     * eventNumber
     *
     * @var string
     */
    protected $eventNumber = '';

    /**
     * disturberMessage
     *
     * @var string
     */
    protected $disturberMessage = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

    /**
     * shortDescription
     *
     * @var string
     */
    protected $shortDescription = '';

    /**
     * sponsorsTitle
     *
     * @var string
     */
    protected $sponsorsTitle = '';

    /**
     * referentsTitle
     *
     * @var string
     */
    protected $referentsTitle = '';

    /**
     * seoTitle
     *
     * @var string
     */
    protected $seoTitle = '';

    /**
     * seoDescription
     *
     * @var string
     */
    protected $seoDescription = '';

    /**
     * eventAttendanceMode
     *
     * @var string
     */
    protected $eventAttendanceMode = '';

    /**
     * showInNews
     *
     * @var bool
     * @deprecated
     */
    protected $showInNews = false;

    /**
     * modifiedAtRecursive
     *
     * @var int
     */
    protected $modifiedAtRecursive = 0;

    /**
     * newsText
     *
     * @var string
     * @deprecated
     */
    protected $newsText = '';

    /**
     * learningObjective
     *
     * @var string
     * @deprecated
     */
    protected $learningObjective = '';

    /**
     * alternativeEvents
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Event>
     */
    protected $alternativeEvents = null;

    /**
     * images
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @deprecated
     */
    protected $images = null;

    /**
     * attachments
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     * @deprecated
     */
    protected $attachments = null;

    /**
     * eventAttachments
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment>
     */
    protected $eventAttachments = null;

    /**
     * eventImages
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventImage>
     */
    protected $eventImages = null;

    /**
     * registrationPossible
     * @var bool
     * @deprecated
     */
    protected $registrationPossible = false;

    /**
     * minParticipants
     *
     * @var int
     * @deprecated
     */
    protected $minParticipants = 0;

    /**
     * maxParticipants
     *
     * @var int
     * @deprecated
     */
    protected $maxParticipants = 0;

    /**
     * participants
     *
     * @var int
     * @deprecated
     */
    protected $participants = 0;

    /**
     * speakers
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker>
     * @deprecated
     */
    protected $speakers = null;

    /**
     * referents
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Referent>
     */
    protected $referents = null;

    /**
     * sponsors
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor>
     */
    protected $sponsors = null;

    /**
     * contactPersons
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson>
     */
    protected $contactPersons = null;

    /**
     * timeRanges
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $timeRanges = null;

    /**
     * eventSessions
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $eventSessions = null;

    /**
     * location
     * @deprecated
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\Location
     */
    protected $location = null;

    /**
     * categories
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category>
     */
    protected $categories = null;

    /**
     * organizer
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer>
     */
    protected $organizer = null;

    /**
     * targetGroups
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup>
     */
    protected $targetGroups = null;

    /**
     * filterCategories
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory>
     */
    protected $filterCategories = null;

    /**
     * eventTicketPriceVariants
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant>
     */
    protected $eventTicketPriceVariants = null;

    /**
     * locations
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Location>
     */
    protected $locations = null;

    /**
     * hash
     *
     * @var string
     * @deprecated
     */
    protected $hash = '';

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
        $this->speakers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->timeRanges = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->categories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->organizer = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->filterCategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->targetGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->images = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->attachments = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->alternativeEvents = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->eventAttachments = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->eventImages = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->referents = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->sponsors = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->contactPersons = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->eventSessions = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->eventTicketPriceVariants = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->locations = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a Speaker
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speaker
     * @return void
     * @deprecated
     */
    public function addSpeaker(\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speaker)
    {
        $this->getSpeakers()->attach($speaker);
    }

    /**
     * Removes a Speaker
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speakerToRemove The Speaker to be removed
     * @return void
     * @deprecated
     */
    public function removeSpeaker(\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speakerToRemove)
    {
        $this->getSpeakers()->detach($speakerToRemove);
    }

    /**
     * Returns the speakers
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker> speakers
     * @deprecated
     */
    public function getSpeakers()
    {
        if (null === $this->speakers) {
            $this->speakers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->speakers;
    }

    /**
     * Sets the speakers
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker> $speakers
     * @return void
     * @deprecated
     */
    public function setSpeakers(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $speakers)
    {
        $this->speakers = $speakers;
    }

    /**
     * Returns the status
     *
     * @return int $status
     * @deprecated
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param int $status
     * @return void
     * @deprecated
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the canceled
     *
     * @return bool $canceled
     * @deprecated
     */
    public function getCanceled()
    {
        return $this->canceled;
    }

    /**
     * Sets the canceled
     *
     * @param bool $canceled
     * @return void
     * @deprecated
     */
    public function setCanceled($canceled)
    {
        $this->canceled = $canceled;
    }

    /**
     * Returns the boolean state of canceled
     *
     * @return bool
     * @deprecated
     */
    public function isCanceled()
    {
        return $this->canceled;
    }

    /**
     * Returns the url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the url
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
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
     * Returns the subtitle
     *
     * @return string $subtitle
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Sets the subtitle
     *
     * @param string $subtitle
     * @return void
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the shortDescription
     *
     * @return string $shortDescription
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Sets the shortDescription
     *
     * @param string $shortDescription
     * @return void
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;
    }

    /**
     * Returns the showInNews
     *
     * @return bool $showInNews
     * @deprecated
     */
    public function getShowInNews()
    {
        return $this->showInNews;
    }

    /**
     * Sets the showInNews
     *
     * @param bool $showInNews
     * @return void
     * @deprecated
     */
    public function setShowInNews($showInNews)
    {
        $this->showInNews = $showInNews;
    }

    /**
     * Returns the boolean state of showInNews
     *
     * @return bool
     * @deprecated
     */
    public function isShowInNews()
    {
        return $this->showInNews;
    }

    /**
     * Returns the newsText
     *
     * @return string $newsText
     * @deprecated
     */
    public function getNewsText()
    {
        return $this->newsText;
    }

    /**
     * Sets the newsText
     *
     * @param string $newsText
     * @return void
     * @deprecated
     */
    public function setNewsText($newsText)
    {
        $this->newsText = $newsText;
    }

    /**
     * Returns the learningObjective
     *
     * @return string $learningObjective
     * @deprecated
     */
    public function getLearningObjective()
    {
        return $this->learningObjective;
    }

    /**
     * Sets the learningObjective
     *
     * @param string $learningObjective
     * @return void
     * @deprecated
     */
    public function setLearningObjective($learningObjective)
    {
        $this->learningObjective = $learningObjective;
    }

    /**
     * Adds an Image
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $image
     * @return void
     * @deprecated
     */
    public function addImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $image)
    {
        $this->images->attach($image);
    }

    /**
     * Removes an Image
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $imageToRemove The Image to be removed
     * @return void
     * @deprecated
     */
    public function removeImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $imageToRemove)
    {
        $this->images->detach($imageToRemove);
    }

    /**
     * Returns the images
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> images
     * @deprecated
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Sets the images
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $images
     * @return void
     * @deprecated
     */
    public function setImages(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $images)
    {
        $this->images = $images;
    }

    /**
     * Adds an Attachment
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $attachment
     * @return void
     * @deprecated
     */
    public function addAttachment(\TYPO3\CMS\Extbase\Domain\Model\FileReference $attachment)
    {
        $this->attachments->attach($attachment);
    }

    /**
     * Removes an Attachment
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $attachmentToRemove The Attachment to be removed
     * @return void
     * @deprecated
     */
    public function removeAttachment(\TYPO3\CMS\Extbase\Domain\Model\FileReference $attachmentToRemove)
    {
        $this->attachments->detach($attachmentToRemove);
    }

    /**
     * Returns the attachments
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> attachments
     * @deprecated
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Sets the attachments
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $attachments
     * @return void
     * @deprecated
     */
    public function setAttachments(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Returns the registrationPossible
     *
     * @return bool $registrationPossible
     * @deprecated
     */
    public function getRegistrationPossible()
    {
        return $this->registrationPossible;
    }

    /**
     * Sets the registrationPossible
     *
     * @param bool $registrationPossible
     * @return void
     * @deprecated
     */
    public function setRegistrationPossible($registrationPossible)
    {
        $this->registrationPossible = $registrationPossible;
    }

    /**
     * Returns the boolean state of registrationPossible
     *
     * @return bool
     * @deprecated
     */
    public function isRegistrationPossible()
    {
        return $this->registrationPossible;
    }

    /**
     * Returns the minParticipants
     *
     * @return int $minParticipants
     * @deprecated
     */
    public function getMinParticipants()
    {
        return $this->minParticipants;
    }

    /**
     * Sets the minParticipants
     *
     * @param int $minParticipants
     * @return void
     * @deprecated
     */
    public function setMinParticipants($minParticipants)
    {
        $this->minParticipants = $minParticipants;
    }

    /**
     * Returns the maxParticipants
     *
     * @return int $maxParticipants
     * @deprecated
     */
    public function getMaxParticipants()
    {
        return $this->maxParticipants;
    }

    /**
     * Sets the maxParticipants
     *
     * @param int $maxParticipants
     * @return void
     * @deprecated
     */
    public function setMaxParticipants($maxParticipants)
    {
        $this->maxParticipants = $maxParticipants;
    }

    /**
     * Returns the participants
     *
     * @return int $participants
     * @deprecated
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Sets the participants
     *
     * @param int $participants
     * @return void
     * @deprecated
     */
    public function setParticipants($participants)
    {
        $this->participants = $participants;
    }

    /**
     * Returns the location
     *
     * @return \BrainAppeal\CampusEventsConnector\Domain\Model\Location $location
     * @deprecated
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Location $location
     * @return void
     * @deprecated
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Adds a Category
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Category $category
     * @return void
     */
    public function addCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\Category $category)
    {
        $this->getCategories()->attach($category);
    }

    /**
     * Removes a Category
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Category $categoryToRemove The Category to be removed
     * @return void
     */
    public function removeCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\Category $categoryToRemove)
    {
        $this->getCategories()->detach($categoryToRemove);
    }

    /**
     * Returns the categories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category> $categories
     */
    public function getCategories()
    {
        if (null === $this->categories) {
            $this->categories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->categories;
    }

    /**
     * Sets the categories
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category> $categories
     * @return void
     */
    public function setCategories(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Adds a Organizer
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizer
     * @return void
     */
    public function addOrganizer(\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizer)
    {
        $this->getOrganizer()->attach($organizer);
    }

    /**
     * Removes a Organizer
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizerToRemove The Organizer to be removed
     * @return void
     */
    public function removeOrganizer(\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizerToRemove)
    {
        $this->getOrganizer()->detach($organizerToRemove);
    }

    /**
     * Returns the organizer
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer> $organizer
     */
    public function getOrganizer()
    {
        if (null === $this->organizer) {
            $this->organizer = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->organizer;
    }

    /**
     * Sets the organizer
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer> $organizer
     * @return void
     */
    public function setOrganizer(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $organizer)
    {
        $this->organizer = $organizer;
    }

    /**
     * Adds a Timerange
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRange
     * @return void
     */
    public function addTimeRange(\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRange)
    {
        $this->getTimeRanges()->attach($timeRange);
    }

    /**
     * Removes a Timerange
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRangeToRemove The TimeRange to be removed
     * @return void
     */
    public function removeTimeRange(\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRangeToRemove)
    {
        $this->getTimeRanges()->detach($timeRangeToRemove);
    }

    /**
     * Returns the timeRanges
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange> timeRanges
     */
    public function getTimeRanges()
    {
        if (null === $this->timeRanges) {
            $this->timeRanges = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->timeRanges;
    }

    /**
     * Sets the timeRanges
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange> $timeRanges
     * @return void
     * @deprecated
     */
    public function setTimeRanges(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $timeRanges)
    {
        $this->timeRanges = $timeRanges;
    }

    /**
     * Adds a TargetGroup
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroup
     * @return void
     */
    public function addTargetGroup(\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroup)
    {
        $this->getTargetGroups()->attach($targetGroup);
    }

    /**
     * Removes a TargetGroup
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove The TargetGroup to be removed
     * @return void
     */
    public function removeTargetGroup(\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove)
    {
        $this->getTargetGroups()->detach($targetGroupToRemove);
    }

    /**
     * Returns the targetGroups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> targetGroups
     */
    public function getTargetGroups()
    {
        if (null === $this->targetGroups) {
            $this->targetGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->targetGroups;
    }

    /**
     * Sets the targetGroups
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> $targetGroups
     * @return void
     */
    public function setTargetGroups(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $targetGroups)
    {
        $this->targetGroups = $targetGroups;
    }

    /**
     * Adds a FilterCategory
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategory
     * @return void
     */
    public function addFilterCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategory)
    {
        $this->getFilterCategories()->attach($filterCategory);
    }

    /**
     * Removes a FilterCategory
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove The FilterCategory to be removed
     * @return void
     */
    public function removeFilterCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove)
    {
        $this->getFilterCategories()->detach($filterCategoryToRemove);
    }

    /**
     * Returns the filterCategories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> filterCategories
     */
    public function getFilterCategories()
    {
        if (null === $this->filterCategories) {
            $this->filterCategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        }
        return $this->filterCategories;
    }

    /**
     * Sets the filterCategories
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> $filterCategories
     * @return void
     */
    public function setFilterCategories(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $filterCategories)
    {
        $this->filterCategories = $filterCategories;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return Event
     * @deprecated
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Returns a copy from earliest date time
     *
     * @return int
     */
    public function getStartTstamp()
    {
        if (!$this->startTstamp) {
            $tstamp = 0;
            foreach ($this->getTimeRanges() as $timeRange) {
                /** @var TimeRange $timeRange */
                if ($timeRange->getStartTstamp() > 0 && (0 === $tstamp || $timeRange->getStartTstamp() < $tstamp)) {
                    $tstamp = $timeRange->getStartTstamp();
                }
            }

            if ($tstamp > 0) {
                $this->startTstamp = $tstamp;
            }
        }
        return $this->startTstamp;
    }

    /**
     * Returns the event end timestamp
     *
     * @return int
     */
    public function getEndTstamp()
    {
        if (!$this->endTstamp) {
            $tstamp = 0;
            foreach ($this->getTimeRanges() as $timeRange) {
                /** @var TimeRange $timeRange */
                if ($timeRange->getEndTstamp() > $tstamp) {
                    $tstamp = $timeRange->getEndTstamp();
                }
            }

            if ($tstamp > 0) {
                $this->endTstamp = $tstamp;
            }
        }
        return $this->endTstamp;
    }

    /**
     * @return int
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @param int $orderType
     */
    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;
    }

    /**
     * @return string
     */
    public function getExternalOrderUrl()
    {
        return $this->externalOrderUrl;
    }

    /**
     * @param string $externalOrderUrl
     */
    public function setExternalOrderUrl($externalOrderUrl)
    {
        $this->externalOrderUrl = $externalOrderUrl;
    }

    /**
     * @return string
     */
    public function getExternalOrderEmailAddress()
    {
        return $this->externalOrderEmailAddress;
    }

    /**
     * @param string $externalOrderEmailAddress
     */
    public function setExternalOrderEmailAddress($externalOrderEmailAddress)
    {
        $this->externalOrderEmailAddress = $externalOrderEmailAddress;
    }

    /**
     * @return string
     */
    public function getDirectRegistrationUrl()
    {
        return $this->directRegistrationUrl;
    }

    /**
     * @param string $directRegistrationUrl
     */
    public function setDirectRegistrationUrl(string $directRegistrationUrl)
    {
        $this->directRegistrationUrl = $directRegistrationUrl;
    }

    /**
     * @return string
     */
    public function getEventNumber()
    {
        return $this->eventNumber;
    }

    /**
     * @param string $eventNumber
     */
    public function setEventNumber($eventNumber)
    {
        $this->eventNumber = $eventNumber;
    }

    /**
     * @return string
     */
    public function getDisturberMessage()
    {
        return $this->disturberMessage;
    }

    /**
     * @param string $disturberMessage
     */
    public function setDisturberMessage($disturberMessage)
    {
        $this->disturberMessage = $disturberMessage;
    }

    /**
     * @return string
     */
    public function getSponsorsTitle()
    {
        return $this->sponsorsTitle;
    }

    /**
     * @param string $sponsorsTitle
     */
    public function setSponsorsTitle($sponsorsTitle)
    {
        $this->sponsorsTitle = $sponsorsTitle;
    }

    /**
     * @return string
     */
    public function getReferentsTitle()
    {
        return $this->referentsTitle;
    }

    /**
     * @param string $referentsTitle
     */
    public function setReferentsTitle($referentsTitle)
    {
        $this->referentsTitle = $referentsTitle;
    }

    /**
     * @return string
     */
    public function getSeoTitle()
    {
        return $this->seoTitle;
    }

    /**
     * @param string $seoTitle
     */
    public function setSeoTitle($seoTitle)
    {
        $this->seoTitle = $seoTitle;
    }

    /**
     * @return string
     */
    public function getSeoDescription()
    {
        return $this->seoDescription;
    }

    /**
     * @param string $seoDescription
     */
    public function setSeoDescription($seoDescription)
    {
        $this->seoDescription = $seoDescription;
    }

    /**
     * @return string
     */
    public function getEventAttendanceMode()
    {
        return $this->eventAttendanceMode;
    }

    /**
     * @param string $eventAttendanceMode
     */
    public function setEventAttendanceMode($eventAttendanceMode)
    {
        $this->eventAttendanceMode = $eventAttendanceMode;
    }

    /**
     * @return int
     */
    public function getModifiedAtRecursive()
    {
        return $this->modifiedAtRecursive;
    }

    /**
     * @param int $modifiedAtRecursive
     */
    public function setModifiedAtRecursive($modifiedAtRecursive)
    {
        $this->modifiedAtRecursive = $modifiedAtRecursive;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getAlternativeEvents()
    {
        return $this->alternativeEvents;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $alternativeEvents
     */
    public function setAlternativeEvents($alternativeEvents)
    {
        $this->alternativeEvents = $alternativeEvents;
    }

    /**
     * Adds an AlternativeEvent
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $alternativeEvent
     * @return void
     */
    public function addAlternativeEvent(\BrainAppeal\CampusEventsConnector\Domain\Model\Event $alternativeEvent)
    {
        $this->getAlternativeEvents()->attach($alternativeEvent);
    }

    /**
     * Removes an AlternativeEvent
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $alternativeEventToRemove The alternativeEvent to be removed
     * @return void
     */
    public function removeAlternativeEvent(\BrainAppeal\CampusEventsConnector\Domain\Model\Event $alternativeEventToRemove)
    {
        $this->getAlternativeEvents()->detach($alternativeEventToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getEventAttachments()
    {
        return $this->eventAttachments;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventAttachments
     */
    public function setEventAttachments($eventAttachments)
    {
        $this->eventAttachments = $eventAttachments;
    }

    /**
     * Adds an EventAttachment
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment $eventAttachment
     * @return void
     */
    public function addEventAttachment(\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment $eventAttachment)
    {
        $this->getEventAttachments()->attach($eventAttachment);
    }

    /**
     * Removes an EventAttachment
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment $eventAttachmentToRemove The EventAttachment to be removed
     * @return void
     */
    public function removeEventAttachment(\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment $eventAttachmentToRemove)
    {
        $this->getEventAttachments()->detach($eventAttachmentToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getEventImages()
    {
        return $this->eventImages;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventImages
     */
    public function setEventImages($eventImages)
    {
        $this->eventImages = $eventImages;
    }

    /**
     * Adds an EventImage
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventImage $eventImage
     * @return void
     */
    public function addEventImage(\BrainAppeal\CampusEventsConnector\Domain\Model\EventImage $eventImage)
    {
        $this->getEventImages()->attach($eventImage);
    }

    /**
     * Removes an EventImage
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventImage $eventImageToRemove The eventImage to be removed
     * @return void
     */
    public function removeEventImage(\BrainAppeal\CampusEventsConnector\Domain\Model\EventImage $eventImageToRemove)
    {
        $this->getEventImages()->detach($eventImageToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getReferents()
    {
        return $this->referents;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $referents
     */
    public function setReferents($referents)
    {
        $this->referents = $referents;
    }

    /**
     * Adds a Referent
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Referent $referent
     * @return void
     */
    public function addReferent(\BrainAppeal\CampusEventsConnector\Domain\Model\Referent $referent)
    {
        $this->getReferents()->attach($referent);
    }

    /**
     * Removes a Referent
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Referent $referentToRemove The Referent to be removed
     * @return void
     */
    public function removeReferent(\BrainAppeal\CampusEventsConnector\Domain\Model\Referent $referentToRemove)
    {
        $this->getReferents()->detach($referentToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getSponsors()
    {
        return $this->sponsors;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $sponsors
     */
    public function setSponsors($sponsors)
    {
        $this->sponsors = $sponsors;
    }

    /**
     * Adds a Sponsor
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor $sponsor
     * @return void
     */
    public function addSponsor(\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor $sponsor)
    {
        $this->getSponsors()->attach($sponsor);
    }

    /**
     * Removes a Sponsor
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor $sponsorToRemove The Sponsor to be removed
     * @return void
     */
    public function removeSponsor(\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor $sponsorToRemove)
    {
        $this->getSponsors()->detach($sponsorToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getContactPersons()
    {
        return $this->contactPersons;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $contactPersons
     */
    public function setContactPersons($contactPersons)
    {
        $this->contactPersons = $contactPersons;
    }

    /**
     * Adds a ContactPerson
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPerson
     * @return void
     */
    public function addContactPerson(\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPerson)
    {
        $this->getContactPersons()->attach($contactPerson);
    }

    /**
     * Removes a ContactPerson
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPersonToRemove The ContactPerson to be removed
     * @return void
     */
    public function removeContactPerson(\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPersonToRemove)
    {
        $this->getContactPersons()->detach($contactPersonToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getEventSessions()
    {
        return $this->eventSessions;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventSessions
     */
    public function setEventSessions($eventSessions)
    {
        $this->eventSessions = $eventSessions;
    }

    /**
     * Adds an EventSession
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSession
     * @return void
     */
    public function addEventSession(\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSession)
    {
        $this->getEventSessions()->attach($eventSession);
    }

    /**
     * Removes an EventSession
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSessionToRemove The EventSession to be removed
     * @return void
     */
    public function removeEventSession(\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSessionToRemove)
    {
        $this->getEventSessions()->detach($eventSessionToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getEventTicketPriceVariants()
    {
        return $this->eventTicketPriceVariants;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $eventTicketPriceVariants
     */
    public function setEventTicketPriceVariants($eventTicketPriceVariants)
    {
        $this->eventTicketPriceVariants = $eventTicketPriceVariants;
    }

    /**
     * Adds an EventTicketPriceVariant
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariant
     * @return void
     */
    public function addEventTicketPriceVariant(\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariant)
    {
        $this->getEventTicketPriceVariants()->attach($eventTicketPriceVariant);
    }

    /**
     * Removes an EventTicketPriceVariant
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariantToRemove The EventTicketPriceVariant to be removed
     * @return void
     */
    public function removeEventTicketPriceVariant(\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariantToRemove)
    {
        $this->getEventTicketPriceVariants()->detach($eventTicketPriceVariantToRemove);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $locations
     */
    public function setLocations($locations)
    {
        $this->locations = $locations;
    }

    /**
     * Adds a Location
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Location $location
     * @return void
     */
    public function addLocation(\BrainAppeal\CampusEventsConnector\Domain\Model\Location $location)
    {
        if (null === $this->location) {
            $this->location = $location;
        }
        $this->getLocations()->attach($location);
    }

    /**
     * Removes a Location
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Location $locationToRemove The Location to be removed
     * @return void
     */
    public function removeLocation(\BrainAppeal\CampusEventsConnector\Domain\Model\Location $locationToRemove)
    {
        $this->getLocations()->detach($locationToRemove);
    }
}
