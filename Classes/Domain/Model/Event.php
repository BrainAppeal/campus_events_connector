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
class Event extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    /**
     * status
     *
     * @var int
     */
    protected $status = 0;

    /**
     * canceled
     *
     * @var bool
     */
    protected $canceled = false;

    /**
     * url
     *
     * @var string
     */
    protected $url = '';

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
     * showInNews
     *
     * @var bool
     */
    protected $showInNews = false;

    /**
     * newsText
     *
     * @var string
     */
    protected $newsText = '';

    /**
     * learningObjective
     *
     * @var string
     */
    protected $learningObjective = '';

    /**
     * images
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    protected $images = null;

    /**
     * attachments
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    protected $attachments = null;

    /**
     * registrationPossible
     *
     * @var bool
     */
    protected $registrationPossible = false;

    /**
     * minParticipants
     *
     * @var int
     */
    protected $minParticipants = 0;

    /**
     * maxParticipants
     *
     * @var int
     */
    protected $maxParticipants = 0;

    /**
     * participants
     *
     * @var int
     */
    protected $participants = 0;

    /**
     * speakers
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker>
     */
    protected $speakers = null;

    /**
     * timeRanges
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $timeRanges = null;

    /**
     * location
     *
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
     * hash
     *
     * @var string
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
    }

    /**
     * Adds a Speaker
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speaker
     * @return void
     */
    public function addSpeaker(\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speaker)
    {
        $this->speakers->attach($speaker);
    }

    /**
     * Removes a Speaker
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speakerToRemove The Speaker to be removed
     * @return void
     */
    public function removeSpeaker(\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker $speakerToRemove)
    {
        $this->speakers->detach($speakerToRemove);
    }

    /**
     * Returns the speakers
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker> speakers
     */
    public function getSpeakers()
    {
        return $this->speakers;
    }

    /**
     * Sets the speakers
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Speaker> $speakers
     * @return void
     */
    public function setSpeakers(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $speakers)
    {
        $this->speakers = $speakers;
    }

    /**
     * Returns the status
     *
     * @return int $status
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
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the canceled
     *
     * @return bool $canceled
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
     */
    public function setCanceled($canceled)
    {
        $this->canceled = $canceled;
    }

    /**
     * Returns the boolean state of canceled
     *
     * @return bool
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
     */
    public function setShowInNews($showInNews)
    {
        $this->showInNews = $showInNews;
    }

    /**
     * Returns the boolean state of showInNews
     *
     * @return bool
     */
    public function isShowInNews()
    {
        return $this->showInNews;
    }

    /**
     * Returns the newsText
     *
     * @return string $newsText
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
     */
    public function setNewsText($newsText)
    {
        $this->newsText = $newsText;
    }

    /**
     * Returns the learningObjective
     *
     * @return string $learningObjective
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
     */
    public function removeImage(\TYPO3\CMS\Extbase\Domain\Model\FileReference $imageToRemove)
    {
        $this->images->detach($imageToRemove);
    }

    /**
     * Returns the images
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> images
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
     */
    public function removeAttachment(\TYPO3\CMS\Extbase\Domain\Model\FileReference $attachmentToRemove)
    {
        $this->attachments->detach($attachmentToRemove);
    }

    /**
     * Returns the attachments
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> attachments
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
     */
    public function setAttachments(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Returns the registrationPossible
     *
     * @return bool $registrationPossible
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
     */
    public function setRegistrationPossible($registrationPossible)
    {
        $this->registrationPossible = $registrationPossible;
    }

    /**
     * Returns the boolean state of registrationPossible
     *
     * @return bool
     */
    public function isRegistrationPossible()
    {
        return $this->registrationPossible;
    }

    /**
     * Returns the minParticipants
     *
     * @return int $minParticipants
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
     */
    public function setMinParticipants($minParticipants)
    {
        $this->minParticipants = $minParticipants;
    }

    /**
     * Returns the maxParticipants
     *
     * @return int $maxParticipants
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
     */
    public function setMaxParticipants($maxParticipants)
    {
        $this->maxParticipants = $maxParticipants;
    }

    /**
     * Returns the participants
     *
     * @return int $participants
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
     */
    public function setParticipants($participants)
    {
        $this->participants = $participants;
    }

    /**
     * Returns the location
     *
     * @return \BrainAppeal\CampusEventsConnector\Domain\Model\Location $location
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
        $this->categories->attach($category);
    }

    /**
     * Removes a Category
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Category $categoryToRemove The Category to be removed
     * @return void
     */
    public function removeCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\Category $categoryToRemove)
    {
        $this->categories->detach($categoryToRemove);
    }

    /**
     * Returns the categories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category> $categories
     */
    public function getCategories()
    {
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
        $this->organizer->attach($organizer);
    }

    /**
     * Removes a Organizer
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizerToRemove The Organizer to be removed
     * @return void
     */
    public function removeOrganizer(\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer $organizerToRemove)
    {
        $this->organizer->detach($organizerToRemove);
    }

    /**
     * Returns the organizer
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer> $organizer
     */
    public function getOrganizer()
    {
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
        $this->timeRanges->attach($timeRange);
    }

    /**
     * Removes a Timerange
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRangeToRemove The TimeRange to be removed
     * @return void
     */
    public function removeTimeRange(\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange $timeRangeToRemove)
    {
        $this->timeRanges->detach($timeRangeToRemove);
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
        $this->targetGroups->attach($targetGroup);
    }

    /**
     * Removes a TargetGroup
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove The TargetGroup to be removed
     * @return void
     */
    public function removeTargetGroup(\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup $targetGroupToRemove)
    {
        $this->targetGroups->detach($targetGroupToRemove);
    }

    /**
     * Returns the targetGroups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> targetGroups
     */
    public function getTargetGroups()
    {
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
        $this->filterCategories->attach($filterCategory);
    }

    /**
     * Removes a FilterCategory
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove The FilterCategory to be removed
     * @return void
     */
    public function removeFilterCategory(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $filterCategoryToRemove)
    {
        $this->filterCategories->detach($filterCategoryToRemove);
    }

    /**
     * Returns the filterCategories
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> filterCategories
     */
    public function getFilterCategories()
    {
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
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return Event
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Returns a copy from earliest date time
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        $startDate = null;
        foreach ($this->getTimeRanges() as $timeRange) {
            if (null === $startDate || $timeRange->getStartDate() < $startDate) {
                $startDate = $timeRange->getStartDate();
            }
        }

        if (null !== $startDate) {
            $startDate = clone $startDate;
        }

        return $startDate;
    }

    /**
     * Returns a copy from latest date time
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        $endDate = null;
        foreach ($this->getTimeRanges() as $timeRange) {
            if (null === $endDate || $timeRange->getEndDate() > $endDate) {
                $endDate = $timeRange->getEndDate();
            }
        }

        if (null !== $endDate) {
            $endDate = clone $endDate;
        }

        return $endDate;
    }
}
