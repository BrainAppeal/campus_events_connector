<?php
namespace BrainAppeal\BrainEventConnector\Tests\Unit\Domain\Model;

/**
 * Test case.
 */
class EventsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \BrainAppeal\BrainEventConnector\Domain\Model\Event
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \BrainAppeal\BrainEventConnector\Domain\Model\Event();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getStatusReturnsInitialValueForInt()
    {
        self::assertSame(
            0,
            $this->subject->getStatus()
        );
    }

    /**
     * @test
     */
    public function setStatusForIntSetsStatus()
    {
        $this->subject->setStatus(12);

        self::assertAttributeEquals(
            12,
            'status',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getCanceledReturnsInitialValueForBool()
    {
        self::assertSame(
            false,
            $this->subject->getCanceled()
        );
    }

    /**
     * @test
     */
    public function setCanceledForBoolSetsCanceled()
    {
        $this->subject->setCanceled(true);

        self::assertAttributeEquals(
            true,
            'canceled',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getUrlReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getUrl()
        );
    }

    /**
     * @test
     */
    public function setUrlForStringSetsUrl()
    {
        $this->subject->setUrl('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'url',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getNameReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getName()
        );
    }

    /**
     * @test
     */
    public function setNameForStringSetsName()
    {
        $this->subject->setName('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'name',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getSubtitleReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getSubtitle()
        );
    }

    /**
     * @test
     */
    public function setSubtitleForStringSetsSubtitle()
    {
        $this->subject->setSubtitle('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'subtitle',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getDescriptionReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getDescription()
        );
    }

    /**
     * @test
     */
    public function setDescriptionForStringSetsDescription()
    {
        $this->subject->setDescription('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'description',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getShortDescriptionReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getShortDescription()
        );
    }

    /**
     * @test
     */
    public function setShortDescriptionForStringSetsShortDescription()
    {
        $this->subject->setShortDescription('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'shortDescription',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getShowInNewsReturnsInitialValueForBool()
    {
        self::assertSame(
            false,
            $this->subject->getShowInNews()
        );
    }

    /**
     * @test
     */
    public function setShowInNewsForBoolSetsShowInNews()
    {
        $this->subject->setShowInNews(true);

        self::assertAttributeEquals(
            true,
            'showInNews',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getNewsTextReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getNewsText()
        );
    }

    /**
     * @test
     */
    public function setNewsTextForStringSetsNewsText()
    {
        $this->subject->setNewsText('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'newsText',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getLearningObjectiveReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getLearningObjective()
        );
    }

    /**
     * @test
     */
    public function setLearningObjectiveForStringSetsLearningObjective()
    {
        $this->subject->setLearningObjective('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'learningObjective',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getImagesReturnsInitialValueForFileReference()
    {
        self::assertEquals(
            null,
            $this->subject->getImages()
        );
    }

    /**
     * @test
     */
    public function setImagesForFileReferenceSetsImages()
    {
//        $fileReferenceFixture = new \TYPO3\CMS\Extbase\Domain\Model\FileReference();
//        $this->subject->setImages($fileReferenceFixture);
//
//        self::assertAttributeEquals(
//            $fileReferenceFixture,
//            'images',
//            $this->subject
//        );
    }

    /**
     * @test
     */
    public function getAttachmentsReturnsInitialValueForFileReference()
    {
        self::assertEquals(
            null,
            $this->subject->getAttachments()
        );
    }

    /**
     * @test
     */
    public function setAttachmentsForFileReferenceSetsAttachments()
    {
//        $fileReferenceFixture = new \TYPO3\CMS\Extbase\Domain\Model\FileReference();
//        $this->subject->setAttachments($fileReferenceFixture);
//
//        self::assertAttributeEquals(
//            $fileReferenceFixture,
//            'attachments',
//            $this->subject
//        );
    }

    /**
     * @test
     */
    public function getRegistrationPossibleReturnsInitialValueForBool()
    {
        self::assertSame(
            false,
            $this->subject->getRegistrationPossible()
        );
    }

    /**
     * @test
     */
    public function setRegistrationPossibleForBoolSetsRegistrationPossible()
    {
        $this->subject->setRegistrationPossible(true);

        self::assertAttributeEquals(
            true,
            'registrationPossible',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getMinParticipantsReturnsInitialValueForInt()
    {
        self::assertSame(
            0,
            $this->subject->getMinParticipants()
        );
    }

    /**
     * @test
     */
    public function setMinParticipantsForIntSetsMinParticipants()
    {
        $this->subject->setMinParticipants(12);

        self::assertAttributeEquals(
            12,
            'minParticipants',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getMaxParticipantsReturnsInitialValueForInt()
    {
        self::assertSame(
            0,
            $this->subject->getMaxParticipants()
        );
    }

    /**
     * @test
     */
    public function setMaxParticipantsForIntSetsMaxParticipants()
    {
        $this->subject->setMaxParticipants(12);

        self::assertAttributeEquals(
            12,
            'maxParticipants',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getParticipantsReturnsInitialValueForInt()
    {
        self::assertSame(
            0,
            $this->subject->getParticipants()
        );
    }

    /**
     * @test
     */
    public function setParticipantsForIntSetsParticipants()
    {
        $this->subject->setParticipants(12);

        self::assertAttributeEquals(
            12,
            'participants',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getSpeakersReturnsInitialValueForSpeaker()
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getSpeakers()
        );
    }

    /**
     * @test
     */
    public function setSpeakersForObjectStorageContainingSpeakerSetsSpeakers()
    {
        $speaker = new \BrainAppeal\BrainEventConnector\Domain\Model\Speaker();
        $objectStorageHoldingExactlyOneSpeakers = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneSpeakers->attach($speaker);
        $this->subject->setSpeakers($objectStorageHoldingExactlyOneSpeakers);

        self::assertAttributeEquals(
            $objectStorageHoldingExactlyOneSpeakers,
            'speakers',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function addSpeakerToObjectStorageHoldingSpeakers()
    {
        $speaker = new \BrainAppeal\BrainEventConnector\Domain\Model\Speaker();
        $speakersObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $speakersObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($speaker));
        $this->inject($this->subject, 'speakers', $speakersObjectStorageMock);

        $this->subject->addSpeaker($speaker);
    }

    /**
     * @test
     */
    public function removeSpeakerFromObjectStorageHoldingSpeakers()
    {
        $speaker = new \BrainAppeal\BrainEventConnector\Domain\Model\Speaker();
        $speakersObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $speakersObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($speaker));
        $this->inject($this->subject, 'speakers', $speakersObjectStorageMock);

        $this->subject->removeSpeaker($speaker);
    }

    /**
     * @test
     */
    public function getTimeRangesReturnsInitialValueForTimeRange()
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getTimeRanges()
        );
    }

    /**
     * @test
     */
    public function setTimeRangesForObjectStorageContainingTimeRangeSetsTimeRanges()
    {
        $timeRange = new \BrainAppeal\BrainEventConnector\Domain\Model\TimeRange();
        $objectStorageHoldingExactlyOneTimeRanges = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneTimeRanges->attach($timeRange);
        $this->subject->setTimeRanges($objectStorageHoldingExactlyOneTimeRanges);

        self::assertAttributeEquals(
            $objectStorageHoldingExactlyOneTimeRanges,
            'timeRanges',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function addTimeRangeToObjectStorageHoldingTimeRanges()
    {
        $timeRange = new \BrainAppeal\BrainEventConnector\Domain\Model\TimeRange();
        $timeRangesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $timeRangesObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($timeRange));
        $this->inject($this->subject, 'timeRanges', $timeRangesObjectStorageMock);

        $this->subject->addTimeRange($timeRange);
    }

    /**
     * @test
     */
    public function removeTimeRangeFromObjectStorageHoldingTimeRanges()
    {
        $timeRange = new \BrainAppeal\BrainEventConnector\Domain\Model\TimeRange();
        $timeRangesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $timeRangesObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($timeRange));
        $this->inject($this->subject, 'timeRanges', $timeRangesObjectStorageMock);

        $this->subject->removeTimeRange($timeRange);
    }

    /**
     * @test
     */
    public function getLocationReturnsInitialValueForLocation()
    {
        self::assertEquals(
            null,
            $this->subject->getLocation()
        );
    }

    /**
     * @test
     */
    public function setLocationForLocationSetsLocation()
    {
        $locationFixture = new \BrainAppeal\BrainEventConnector\Domain\Model\Location();
        $this->subject->setLocation($locationFixture);

        self::assertAttributeEquals(
            $locationFixture,
            'location',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getCategoriesReturnsInitialValueForCategory()
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getCategories()
        );
    }

    /**
     * @test
     */
    public function setCategoriesForObjectStorageContainingCategorySetsCategories()
    {
        $category = new \BrainAppeal\BrainEventConnector\Domain\Model\Category();
        $objectStorageHoldingExactlyOneCategories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneCategories->attach($category);
        $this->subject->setCategories($objectStorageHoldingExactlyOneCategories);

        self::assertAttributeEquals(
            $objectStorageHoldingExactlyOneCategories,
            'categories',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function addCategoryToObjectStorageHoldingCategories()
    {
        $category = new \BrainAppeal\BrainEventConnector\Domain\Model\Category();
        $categoriesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoriesObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($category));
        $this->inject($this->subject, 'categories', $categoriesObjectStorageMock);

        $this->subject->addCategory($category);
    }

    /**
     * @test
     */
    public function removeCategoryFromObjectStorageHoldingCategories()
    {
        $category = new \BrainAppeal\BrainEventConnector\Domain\Model\Category();
        $categoriesObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoriesObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($category));
        $this->inject($this->subject, 'categories', $categoriesObjectStorageMock);

        $this->subject->removeCategory($category);
    }

    /**
     * @test
     */
    public function getOrganizerReturnsInitialValueForOrganizer()
    {
        $newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        self::assertEquals(
            $newObjectStorage,
            $this->subject->getOrganizer()
        );
    }

    /**
     * @test
     */
    public function setOrganizerForObjectStorageContainingOrganizerSetsOrganizer()
    {
        $organizer = new \BrainAppeal\BrainEventConnector\Domain\Model\Organizer();
        $objectStorageHoldingExactlyOneOrganizer = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorageHoldingExactlyOneOrganizer->attach($organizer);
        $this->subject->setOrganizer($objectStorageHoldingExactlyOneOrganizer);

        self::assertAttributeEquals(
            $objectStorageHoldingExactlyOneOrganizer,
            'organizer',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function addOrganizerToObjectStorageHoldingOrganizer()
    {
        $organizer = new \BrainAppeal\BrainEventConnector\Domain\Model\Organizer();
        $organizerObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['attach'])
            ->disableOriginalConstructor()
            ->getMock();

        $organizerObjectStorageMock->expects(self::once())->method('attach')->with(self::equalTo($organizer));
        $this->inject($this->subject, 'organizer', $organizerObjectStorageMock);

        $this->subject->addOrganizer($organizer);
    }

    /**
     * @test
     */
    public function removeOrganizerFromObjectStorageHoldingOrganizer()
    {
        $organizer = new \BrainAppeal\BrainEventConnector\Domain\Model\Organizer();
        $organizerObjectStorageMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(['detach'])
            ->disableOriginalConstructor()
            ->getMock();

        $organizerObjectStorageMock->expects(self::once())->method('detach')->with(self::equalTo($organizer));
        $this->inject($this->subject, 'organizer', $organizerObjectStorageMock);

        $this->subject->removeOrganizer($organizer);
    }

    /**
     * @test
     */
    public function getTargetGroupsReturnsInitialValueForTargetGroup()
    {
        self::assertEquals(
            null,
            $this->subject->getTargetGroups()
        );
    }

    /**
     * @test
     */
    public function setTargetGroupsForTargetGroupSetsTargetGroups()
    {
//        $targetGroupsFixture = new \BrainAppeal\BrainEventConnector\Domain\Model\TargetGroup();
//        $this->subject->setTargetGroups($targetGroupsFixture);
//
//        self::assertAttributeEquals(
//            $targetGroupsFixture,
//            'targetGroups',
//            $this->subject
//        );
    }

    /**
     * @test
     */
    public function getFilterCategoriesReturnsInitialValueForFilterCategory()
    {
        self::assertEquals(
            null,
            $this->subject->getFilterCategories()
        );
    }

    /**
     * @test
     */
    public function setFilterCategoriesForFilterCategorySetsFilterCategories()
    {
//        $filterCategoriesFixture = new \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory();
//        $this->subject->setFilterCategories($filterCategoriesFixture);
//
//        self::assertAttributeEquals(
//            $filterCategoriesFixture,
//            'filterCategories',
//            $this->subject
//        );
    }
}
