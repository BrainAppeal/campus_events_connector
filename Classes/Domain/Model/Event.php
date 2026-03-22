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

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Events
 */
class Event extends AbstractImportedEntity
{
    use DatePeriodTrait;
    public const ORDER_TYPE_CAMPUS_EVENTS_ORDER = 0;
    public const ORDER_TYPE_CAMPUS_EVENTS_REGISTRATION = 4;
    public const ORDER_TYPE_NOT_ORDERABLE = 1;
    public const ORDER_TYPE_EXTERNAL_URL = 2;
    public const ORDER_TYPE_EXTERNAL_EMAIL = 3;

    public const EVENT_ATTENDANCE_MODE_UNKNOWN = null;
    public const EVENT_ATTENDANCE_MODE_MIXED = 'mixed';
    public const EVENT_ATTENDANCE_MODE_OFFLINE = 'offline';
    public const EVENT_ATTENDANCE_MODE_ONLINE = 'online';

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
     * @var ?bool
     */
    protected ?bool $canceled = false;

    /**
     * canceled
     *
     * @var bool
     */
    protected ?bool $published = false;

    /**
     * canceled
     *
     * @var bool
     */
    protected ?bool $completed = false;

    /**
     * canceled
     *
     * @var bool
     */
    protected ?bool $archived = false;
    /**
     * url
     *
     * @var ?string
     */
    protected ?string $url = '';

    /**
     * External order url
     *
     * @var ?string
     */
    protected $externalOrderUrl = '';

    /**
     * External order email address
     *
     * @var ?string
     */
    protected $externalOrderEmailAddress = '';

    /**
     * External order email address
     *
     * @var ?string
     */
    protected $externalOrderEmailSubject = '';

    /**
     * External order email address
     *
     * @var ?string
     */
    protected $externalOrderEmailBody = '';

    /**
     * Direct registration url
     *
     * @var ?string
     */
    protected ?string $directRegistrationUrl = '';

    /**
     * name
     *
     * @var ?string
     */
    protected $name = '';

    /**
     * subtitle
     *
     * @var ?string
     */
    protected $subtitle = '';

    /**
     * eventNumber
     *
     * @var ?string
     */
    protected $eventNumber = '';

    /**
     * disturberMessage
     *
     * @var ?string
     */
    protected $disturberMessage = '';

    /**
     * description
     *
     * @var ?string
     */
    protected $description = '';

    /**
     * shortDescription
     *
     * @var ?string
     */
    protected $shortDescription = '';

    /**
     * sponsorsTitle
     *
     * @var ?string
     */
    protected $sponsorsTitle = '';

    /**
     * referentsTitle
     *
     * @var ?string
     */
    protected $referentsTitle = '';

    /**
     * seoTitle
     *
     * @var ?string
     */
    protected $seoTitle = '';

    /**
     * seoDescription
     *
     * @var ?string
     */
    protected $seoDescription = '';

    /**
     * seoRobotsIndex
     *
     * @var bool
     */
    protected ?bool $seoRobotsIndex = false;

    /**
     * seoRobotsFollow
     *
     * @var bool
     */
    protected ?bool $seoRobotsFollow = false;

    /**
     * eventAttendanceMode
     *
     * @var ?string
     */
    protected $eventAttendanceMode = '';

    /**
     * modifiedAtRecursive
     *
     * @var int
     */
    protected $modifiedAtRecursive = 0;

    /**
     * learningObjective
     *
     * @var ?string
     */
    protected $learningObjective = '';

    /**
     * alternativeEvents
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Event>
     */
    #[Lazy]
    protected ?ObjectStorage $alternativeEvents = null;

    /**
     * Event attachments
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment>
     */
    #[Lazy]
    protected ?ObjectStorage $eventAttachments = null;

    /**
     * Event images
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventImage>
     */
    #[Lazy]
    protected ?ObjectStorage $eventImages = null;

    /**
     * Min participants
     *
     * @var ?int
     */
    protected ?int $minParticipants = 0;

    /**
     * Max participants
     *
     * @var ?int
     */
    protected ?int $maxParticipants = 0;

    /**
     * Show available tickets
     *
     * @var bool
     */
    protected ?bool $showAvailableTickets = false;

    /**
     * Available tickets
     *
     * @var ?int
     */
    protected ?int $availableTickets = 0;

    /**
     * ticketCancellationUntil
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $ticketCancellationUntil= null;

    /**
     * Tickets available from
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $ticketsFrom = null;

    /**
     * Tickets available until
     *
     * @var ?\DateTime
     */
    protected ?\DateTime $ticketsTill = null;

    /**
     * Not orderable message
     *
     * @var ?string
     */
    protected $notOrderableMessage = '';

    /**
     * referents
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Referent>
     */
    #[Lazy()]
    protected ?ObjectStorage $referents = null;

    /**
     * sponsors
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor>
     */
    #[Lazy()]
    protected ?ObjectStorage $sponsors = null;

    /**
     * contactPersons
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson>
     */
    #[Lazy()]
    protected ?ObjectStorage $contactPersons = null;

    /**
     * eventSessions
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession>
     */
    #[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]
    #[Lazy()]
    protected ?ObjectStorage $eventSessions = null;

    /**
     * categories
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category>
     */
    #[Lazy()]
    protected ?ObjectStorage $categories = null;

    /**
     * organizer
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer>
     */
    #[Lazy()]
    protected ?ObjectStorage $organizer = null;

    /**
     * targetGroups
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup>
     */
    #[Lazy()]
    protected ?ObjectStorage $targetGroups = null;

    /**
     * viewLists
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ViewList>
     */
    #[Lazy()]
    protected ?ObjectStorage $viewLists = null;

    /**
     * filterCategories
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory>
     */
    #[Lazy()]
    protected ?ObjectStorage $filterCategories = null;

    /**
     * eventTicketPriceVariants
     *
     * @var ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant>
     */
    #[Lazy()]
    protected ?ObjectStorage $eventTicketPriceVariants = null;

    /**
     * locations
     *
     * @var ObjectStorage<Location>
     */
    #[Lazy()]
    protected ?ObjectStorage $locations = null;

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
        $this->categories ??= new ObjectStorage();
        $this->organizer ??= new ObjectStorage();
        $this->filterCategories ??= new ObjectStorage();
        $this->targetGroups ??= new ObjectStorage();
        $this->alternativeEvents ??= new ObjectStorage();
        $this->eventAttachments ??= new ObjectStorage();
        $this->eventImages ??= new ObjectStorage();
        $this->referents ??= new ObjectStorage();
        $this->sponsors ??= new ObjectStorage();
        $this->contactPersons ??= new ObjectStorage();
        $this->eventSessions ??= new ObjectStorage();
        $this->eventTicketPriceVariants ??= new ObjectStorage();
        $this->locations ??= new ObjectStorage();
        $this->viewLists ??= new ObjectStorage();
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
     */
    public function getCanceled(): bool
    {
        return (bool)$this->canceled;
    }

    /**
     * Sets the canceled
     *
     * @param ?bool $canceled
     * @return void
     */
    public function setCanceled(?bool $canceled): void
    {
        $this->canceled = (bool)$canceled;
    }

    /**
     * Returns the boolean state of canceled
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->getCanceled();
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): void
    {
        $this->completed = $completed;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(?bool $archived): void
    {
        $this->archived = $archived;
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
     * Returns the images
     *
     * @return ObjectStorage<EventImage> images
     */
    public function getImages()
    {
        return $this->getEventImages();
    }

    /**
     * Returns the attachments
     *
     * @return ObjectStorage<EventAttachment> attachments
     */
    public function getAttachments()
    {
        return $this->getEventAttachments();
    }

    /**
     * Returns the registrationPossible
     *
     * @return bool $registrationPossible
     */
    public function getRegistrationPossible()
    {
        return $this->published && !$this->isCanceled();
    }

    /**
     * Returns the boolean state of registrationPossible
     *
     * @return bool
     */
    public function isRegistrationPossible()
    {
        return $this->getRegistrationPossible();
    }

    /**
     * Returns the minParticipants
     *
     * @return ?int $minParticipants
     */
    public function getMinParticipants(): ?int
    {
        return $this->minParticipants;
    }

    /**
     * Sets the minParticipants
     *
     * @param ?int $minParticipants
     * @return void
     */
    public function setMinParticipants(?int $minParticipants): void
    {
        $this->minParticipants = (int)$minParticipants;
    }

    /**
     * Returns the maxParticipants
     *
     * @return ?int $maxParticipants
     */
    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    /**
     * Sets the maxParticipants
     *
     * @param ?int $maxParticipants
     * @return void
     */
    public function setMaxParticipants(?int $maxParticipants): void
    {
        $this->maxParticipants = (int)$maxParticipants;
    }

    public function getShowAvailableTickets(): ?bool
    {
        return $this->showAvailableTickets;
    }

    public function setShowAvailableTickets(?bool $showAvailableTickets): void
    {
        $this->showAvailableTickets = $showAvailableTickets;
    }

    public function getAvailableTickets(): ?int
    {
        return $this->availableTickets;
    }

    public function setAvailableTickets(?int $availableTickets): void
    {
        $this->availableTickets = $availableTickets;
    }

    public function getTicketCancellationUntil(): ?\DateTime
    {
        return $this->ticketCancellationUntil;
    }

    public function setTicketCancellationUntil(?\DateTime $ticketCancellationUntil): void
    {
        $this->ticketCancellationUntil = $ticketCancellationUntil;
    }

    public function getTicketsFrom(): ?\DateTime
    {
        return $this->ticketsFrom;
    }

    public function setTicketsFrom(?\DateTime $ticketsFrom): void
    {
        $this->ticketsFrom = $ticketsFrom;
    }

    public function getTicketsTill(): ?\DateTime
    {
        return $this->ticketsTill;
    }

    public function setTicketsTill(?\DateTime $ticketsTill): void
    {
        $this->ticketsTill = $ticketsTill;
    }

    /**
     * Returns the participants
     *
     * @return int $participants
     */
    public function getParticipants()
    {
        return $this->getMaxParticipants();
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category> $categories
     */
    public function getCategories(): ObjectStorage
    {
        if (null === $this->categories) {
            $this->categories = new ObjectStorage();
        }
        return $this->categories;
    }

    /**
     * Sets the categories
     *
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Category> $categories
     * @return void
     */
    public function setCategories(ObjectStorage $categories)
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer> $organizer
     */
    public function getOrganizer(): ObjectStorage
    {
        if (null === $this->organizer) {
            $this->organizer = new ObjectStorage();
        }
        return $this->organizer;
    }

    /**
     * Sets the organizer
     *
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Organizer> $organizer
     * @return void
     */
    public function setOrganizer(ObjectStorage $organizer)
    {
        $this->organizer = $organizer;
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> targetGroups
     */
    public function getTargetGroups(): ObjectStorage
    {
        if (null === $this->targetGroups) {
            $this->targetGroups = new ObjectStorage();
        }
        return $this->targetGroups;
    }

    /**
     * Sets the targetGroups
     *
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup> $targetGroups
     * @return void
     */
    public function setTargetGroups(ObjectStorage $targetGroups)
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> filterCategories
     */
    public function getFilterCategories(): ObjectStorage
    {
        if (null === $this->filterCategories) {
            $this->filterCategories = new ObjectStorage();
        }
        return $this->filterCategories;
    }

    /**
     * Sets the filterCategories
     *
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory> $filterCategories
     * @return void
     */
    public function setFilterCategories(ObjectStorage $filterCategories)
    {
        $this->filterCategories = $filterCategories;
    }

    /**
     * Returns the timeRanges
     *
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange> timeRanges
     */
    public function getTimeRanges(): ObjectStorage
    {
        $timeRanges = new ObjectStorage();
        /** @var EventSession $eventSession */
        foreach ($this->getEventSessions() as $eventSession) {
            /** @var TimeRange $timeRange */
            foreach ($eventSession->getSessionTimePeriods() as $timeRange) {
                $timeRanges->attach($timeRange);
            }
        }
        return $timeRanges;
    }

    /**
     * Returns a copy from the earliest date time
     *
     * @return int
     */
    public function getStartTstamp(): int
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
    public function getEndTstamp(): int
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
    public function getOrderType(): int
    {
        return $this->orderType;
    }

    /**
     * @param int $orderType
     */
    public function setOrderType($orderType): void
    {
        $this->orderType = $orderType;
    }

    /**
     * @return ?string
     */
    public function getExternalOrderUrl(): ?string
    {
        return $this->externalOrderUrl;
    }

    /**
     * @param ?string $externalOrderUrl
     */
    public function setExternalOrderUrl(?string $externalOrderUrl): void
    {
        $this->externalOrderUrl = $externalOrderUrl;
    }

    /**
     * @return ?string
     */
    public function getExternalOrderEmailAddress(): ?string
    {
        return $this->externalOrderEmailAddress;
    }

    /**
     * @param ?string $externalOrderEmailAddress
     */
    public function setExternalOrderEmailAddress($externalOrderEmailAddress): void
    {
        $this->externalOrderEmailAddress = $externalOrderEmailAddress;
    }

    public function getExternalOrderEmailSubject(): ?string
    {
        return $this->externalOrderEmailSubject;
    }

    public function setExternalOrderEmailSubject(?string $externalOrderEmailSubject): void
    {
        $this->externalOrderEmailSubject = $externalOrderEmailSubject;
    }

    public function getExternalOrderEmailBody(): ?string
    {
        return $this->externalOrderEmailBody;
    }

    public function setExternalOrderEmailBody(?string $externalOrderEmailBody): void
    {
        $this->externalOrderEmailBody = $externalOrderEmailBody;
    }

    /**
     * @return ?string
     */
    public function getDirectRegistrationUrl(): ?string
    {
        return $this->directRegistrationUrl;
    }

    /**
     * @param ?string $directRegistrationUrl
     */
    public function setDirectRegistrationUrl(?string $directRegistrationUrl): void
    {
        $this->directRegistrationUrl = $directRegistrationUrl;
    }

    /**
     * @return ?string
     */
    public function getEventNumber(): ?string
    {
        return $this->eventNumber;
    }

    /**
     * @param ?string $eventNumber
     */
    public function setEventNumber(?string $eventNumber)
    {
        $this->eventNumber = $eventNumber;
    }

    /**
     * @return ?string
     */
    public function getDisturberMessage(): ?string
    {
        return $this->disturberMessage;
    }

    /**
     * @param ?string $disturberMessage
     */
    public function setDisturberMessage(?string $disturberMessage)
    {
        $this->disturberMessage = $disturberMessage;
    }

    /**
     * @return ?string
     */
    public function getSponsorsTitle(): ?string
    {
        return $this->sponsorsTitle;
    }

    /**
     * @param ?string $sponsorsTitle
     */
    public function setSponsorsTitle(?string $sponsorsTitle)
    {
        $this->sponsorsTitle = $sponsorsTitle;
    }

    /**
     * @return ?string
     */
    public function getReferentsTitle(): ?string
    {
        return $this->referentsTitle;
    }

    /**
     * @param ?string $referentsTitle
     */
    public function setReferentsTitle(?string $referentsTitle)
    {
        $this->referentsTitle = $referentsTitle;
    }

    /**
     * @return ?string
     */
    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    /**
     * @param ?string $seoTitle
     */
    public function setSeoTitle(?string $seoTitle)
    {
        $this->seoTitle = $seoTitle;
    }

    /**
     * @return ?string
     */
    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    /**
     * @param ?string $seoDescription
     */
    public function setSeoDescription(?string $seoDescription)
    {
        $this->seoDescription = $seoDescription;
    }

    public function getSeoRobotsIndex(): ?bool
    {
        return $this->seoRobotsIndex;
    }

    public function setSeoRobotsIndex(?bool $seoRobotsIndex): void
    {
        $this->seoRobotsIndex = $seoRobotsIndex;
    }

    public function getSeoRobotsFollow(): ?bool
    {
        return $this->seoRobotsFollow;
    }

    public function setSeoRobotsFollow(?bool $seoRobotsFollow): void
    {
        $this->seoRobotsFollow = $seoRobotsFollow;
    }

    /**
     * @return ?string
     */
    public function getEventAttendanceMode()
    {
        return $this->eventAttendanceMode;
    }

    /**
     * @param ?string $eventAttendanceMode
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Event>
     */
    public function getAlternativeEvents(): ObjectStorage
    {
        if ($this->alternativeEvents === null) {
            $this->alternativeEvents = new ObjectStorage();
        }
        return $this->alternativeEvents;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Event> $alternativeEvents
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment>
     */
    public function getEventAttachments(): ObjectStorage
    {
        if ($this->eventAttachments === null) {
            $this->eventAttachments = new ObjectStorage();
        }
        return $this->eventAttachments;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment> $eventAttachments
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
     * @return ObjectStorage<FileReference>
     */
    public function getAttachmentFileReferences(): ObjectStorage
    {
        /** @var ObjectStorage<FileReference> $fileReferences */
        $fileReferences = new ObjectStorage();
        foreach ($this->getEventAttachments() as $eventAttachment) {
            /** @var EventAttachment $eventAttachment */
            $fileReference = $eventAttachment->getAttachmentFile();
            try {
                if (($fileReference instanceof FileReference)) {
                    $fileReference->getOriginalResource();
                    $fileReferences->attach($fileReference);
                }
            } catch (ResourceDoesNotExistException $e) {
                // File reference is broken, do not add it to the list of file references
                continue;
            }
        }
        return $fileReferences;
    }

    /**
     * @return ObjectStorage<EventImage>
     */
    public function getEventImages(): ObjectStorage
    {
        if ($this->eventImages === null) {
            $this->eventImages = new ObjectStorage();
        }
        return $this->eventImages;
    }

    /**
     * @param ObjectStorage<EventImage> $eventImages
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
     * @return ObjectStorage<FileReference>
     */
    public function getImageFileReferences(): ObjectStorage
    {
        /** @var ObjectStorage<FileReference> $fileReferences */
        $fileReferences = new ObjectStorage();
        foreach ($this->getEventImages() as $eventImage) {
            /** @var EventImage $eventImage */
            $fileReference = $eventImage->getImageFile();
            try {
                if (($fileReference instanceof FileReference)) {
                    $fileReference->getOriginalResource();
                    $fileReferences->attach($fileReference);
                }
            } catch (ResourceDoesNotExistException $e) {
                // File reference is broken, do not add it to the list of file references
                continue;
            }
        }
        return $fileReferences;
    }

    /**
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Referent>
     */
    public function getReferents(): ObjectStorage
    {
        if ($this->referents === null) {
            $this->referents = new ObjectStorage();
        }
        return $this->referents;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Referent> $referents
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
     * Returns the referents
     *
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Referent>
     * @deprecated
     */
    public function getSpeakers(): ObjectStorage
    {
        return $this->getReferents();
    }

    /**
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor>
     */
    public function getSponsors(): ObjectStorage
    {
        if ($this->sponsors === null) {
            $this->sponsors = new ObjectStorage();
        }
        return $this->sponsors;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor> $sponsors
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
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson>
     */
    public function getContactPersons(): ObjectStorage
    {
        if (null === $this->contactPersons) {
            $this->contactPersons = new ObjectStorage();
        }
        return $this->contactPersons;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson> $contactPersons
     */
    public function setContactPersons($contactPersons): void
    {
        $this->contactPersons = $contactPersons;
    }

    /**
     * Adds a ContactPerson
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPerson
     * @return void
     */
    public function addContactPerson(\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPerson): void
    {
        $this->getContactPersons()->attach($contactPerson);
    }

    /**
     * Removes a ContactPerson
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPersonToRemove The ContactPerson to be removed
     * @return void
     */
    public function removeContactPerson(\BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson $contactPersonToRemove): void
    {
        $this->getContactPersons()->detach($contactPersonToRemove);
    }

    /**
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession>
     */
    public function getEventSessions(): ObjectStorage
    {
        if (null === $this->eventSessions) {
            $this->eventSessions = new ObjectStorage();
        }
        return $this->eventSessions;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession> $eventSessions
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
    public function addEventSession(\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSession): void
    {
        $this->getEventSessions()->attach($eventSession);
    }

    /**
     * Removes an EventSession
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSessionToRemove The EventSession to be removed
     * @return void
     */
    public function removeEventSession(\BrainAppeal\CampusEventsConnector\Domain\Model\EventSession $eventSessionToRemove): void
    {
        $this->getEventSessions()->detach($eventSessionToRemove);
    }

    /**
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant>
     */
    public function getEventTicketPriceVariants(): ObjectStorage
    {
        if (null === $this->eventTicketPriceVariants) {
            $this->eventTicketPriceVariants = new ObjectStorage();
        }
        return $this->eventTicketPriceVariants;
    }

    /**
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant> $eventTicketPriceVariants
     */
    public function setEventTicketPriceVariants($eventTicketPriceVariants): void
    {
        $this->eventTicketPriceVariants = $eventTicketPriceVariants;
    }

    /**
     * Adds an EventTicketPriceVariant
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariant
     * @return void
     */
    public function addEventTicketPriceVariant(\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariant): void
    {
        $this->getEventTicketPriceVariants()->attach($eventTicketPriceVariant);
    }

    /**
     * Removes an EventTicketPriceVariant
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariantToRemove The EventTicketPriceVariant to be removed
     * @return void
     */
    public function removeEventTicketPriceVariant(\BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant $eventTicketPriceVariantToRemove): void
    {
        $this->getEventTicketPriceVariants()->detach($eventTicketPriceVariantToRemove);
    }

    public function getLocation(): ?Location
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->getLocations() as $location) {
            return $location;
        }
        return null;
    }

    /**
     * @return ObjectStorage<Location>
     */
    public function getLocations(): ObjectStorage
    {
        if (null === $this->locations) {
            $this->locations = new ObjectStorage();
        }
        return $this->locations;
    }

    public function getNotOrderableMessage(): ?string
    {
        return $this->notOrderableMessage;
    }

    public function setNotOrderableMessage(?string $notOrderableMessage): void
    {
        $this->notOrderableMessage = $notOrderableMessage;
    }

    /**
     * @param ObjectStorage $locations
     */
    public function setLocations(ObjectStorage $locations): void
    {
        $this->locations = $locations;
    }

    /**
     * Adds a Location
     *
     * @param Location $location
     * @return void
     */
    public function addLocation(Location $location): void
    {
        $this->getLocations()->attach($location);
    }

    /**
     * Removes a Location
     *
     * @param Location $locationToRemove The Location to be removed
     * @return void
     */
    public function removeLocation(Location $locationToRemove): void
    {
        $this->getLocations()->detach($locationToRemove);
    }

    /**
     * Adds a ViewList
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ViewList $viewList
     * @return void
     */
    public function addViewList(\BrainAppeal\CampusEventsConnector\Domain\Model\ViewList $viewList): void
    {
        $this->getViewLists()->attach($viewList);
    }

    /**
     * Removes a ViewList
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\ViewList $viewListToRemove The ViewList to be removed
     * @return void
     */
    public function removeViewList(\BrainAppeal\CampusEventsConnector\Domain\Model\ViewList $viewListToRemove): void
    {
        $this->getViewLists()->detach($viewListToRemove);
    }

    /**
     * Returns the viewLists
     *
     * @return ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ViewList> viewLists
     */
    public function getViewLists(): ObjectStorage
    {
        if (null === $this->viewLists) {
            $this->viewLists = new ObjectStorage();
        }
        return $this->viewLists;
    }

    /**
     * Sets the viewLists
     *
     * @param ObjectStorage<\BrainAppeal\CampusEventsConnector\Domain\Model\ViewList> $viewLists
     * @return void
     */
    public function setViewLists(ObjectStorage $viewLists): void
    {
        $this->viewLists = $viewLists;
    }
}
