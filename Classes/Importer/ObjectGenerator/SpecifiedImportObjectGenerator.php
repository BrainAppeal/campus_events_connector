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

namespace BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator;

use BrainAppeal\CampusEventsConnector\Domain\Model\Category;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Location;
use BrainAppeal\CampusEventsConnector\Domain\Model\Organizer;
use BrainAppeal\CampusEventsConnector\Domain\Model\Speaker;
use BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup;
use BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange;
use BrainAppeal\CampusEventsConnector\Domain\Model\ViewList;
use BrainAppeal\CampusEventsConnector\Importer\FileImporter;
use BrainAppeal\CampusEventsConnector\Utility\DataParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class SpecifiedImportObjectGenerator extends ImportObjectGenerator
{

    /**
     * @inheritdoc
     */
    protected function assignCategoryProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var Category $object */
        $object->setName($data['name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignEventProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var Event $object */
        if (empty($object->getHash()) || $object->getHash() != $data['hash']) {
            $this->setDataChanged();
        }

        $object->setName((string)$data['name']);
        $object->setCanceled($data['canceled']);
        $object->setUrl($data['url'] ?? '');
        $object->setSubtitle((string)$data['subtitle']);
        $object->setDescription((string)$data['description']);
        $object->setShortDescription((string)$data['short_description']);
        $object->setShowInNews($data['show_in_news']);
        $object->setNewsText((string)$data['news_text']);
        $object->setStatus($data['status']['id']);
        $object->setLearningObjective($data['learning_objective']);
        $object->setRegistrationPossible($data['registration_possible']);
        $object->setMinParticipants($data['min_participants']);
        $object->setMaxParticipants($data['max_participants']);
        $object->setParticipants($data['participants']);
        $object->setHash((string)$data['hash']);

        /** @var TimeRange[] $timeRanges */
        $timeRanges = $this->generateMultiple(TimeRange::class, $data['timeranges']??[]);
        foreach ($timeRanges as $timeRange) {
            $object->addTimeRange($timeRange);
        }

        if (!empty($data['referents'])) {
            /** @var Speaker[] $speakers */
            $speakers = $this->generateMultiple(Speaker::class, $data['referents']);
            foreach ($speakers as $speaker) {
                $object->addSpeaker($speaker);
            }
        }

        $dataOrganizers = [];
        if (!empty($data['organizers'])){
            $dataOrganizers = $data['organizers'];
        } elseif (!empty($data['organizer'])){
            $dataOrganizers = $data['organizer'];
        }
        /** @var Organizer[] $organizers */
        $organizers = $this->generateMultiple(Organizer::class, $dataOrganizers);
        foreach ($organizers as $organizer) {
            $object->addOrganizer($organizer);
        }

        /** @var TargetGroup[] $targetGroups */
        $targetGroups = $this->generateMultiple(TargetGroup::class, $data['target_groups']??[]);
        foreach ($targetGroups as $targetGroup) {
            $object->addTargetGroup($targetGroup);
        }

        /** @var FilterCategory[] $filterCategories */
        $filterCategories = $this->generateMultiple(FilterCategory::class, $data['filter_categories']??[]);
        foreach ($filterCategories as $filterCategory) {
            $object->addFilterCategory($filterCategory);
        }

        if (!empty($data['view_lists'])) {
            /** @var ViewList[] $viewLists */
            $viewLists = $this->generateMultiple(ViewList::class, $data['view_lists']);
            foreach ($viewLists as $viewList) {
                $object->addViewList($viewList);
            }
        } else {
            $object->setViewLists(new ObjectStorage());
        }

        /** @var Category[] $categories */
        $categories = $this->generateMultiple(Category::class, $data['categories']??[]);
        foreach ($categories as $category) {
            $object->addCategory($category);
        }

        $location = null;
        if (!empty($data['location']['id'])) {
            $location = $this->generate(Location::class, (int) $data['location']['id'], $data['location']);
            /** @var Location $location */
        }
        $object->setLocation($location);

        /** @var FileImporter $fileImporter */
        $fileImporter = GeneralUtility::makeInstance(FileImporter::class);
        foreach (($data['images']??[]) as $attachmentData) {
            $fileImporter->enqueueFileMapping($object, 'images', $attachmentData);
        }

        /** @var FileImporter $fileImporter */
        $fileImporter = GeneralUtility::makeInstance(FileImporter::class);
        foreach (($data['attachments']??[]) as $attachmentData) {
            $fileImporter->enqueueFileMapping($object, 'attachments', $attachmentData);
        }
    }

    /**
     * @inheritdoc
     */
    protected function assignFilterCategoryProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var FilterCategory $object */
        $object->setName($data['name']);
        $object->setParent($this->generate($class, (int) $data['parent_id'], null));
    }

    /**
     * @inheritdoc
     */
    protected function assignLocationProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var Location $object */
        $object->setName($data['name']);
        $object->setStreetName($data['street_name'] ?? '');
        $object->setTown($data['town'] ?? '');
        $object->setZipCode($data['zip_code'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignOrganizerProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var Organizer $object */
        $object->setName($data['name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignSpeakerProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var Speaker $object */
        $object->setTitle($data['title'] ?? '');
        $object->setFirstName($data['first_name'] ?? '');
        $object->setLastName($data['last_name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignTargetGroupProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var TargetGroup $object */
        $object->setName($data['name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignViewListProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var ViewList $object */
        $object->setName($data['name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignTimeRangeProperties(string $class, ImportedModelInterface $object, array $data): void
    {
        /** @var TimeRange $object */
        if ($tstamp = $this->strToTime($data['end_date'])) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $this->strToTime($data['start_date'])) {
            $object->setStartTstamp($tstamp);
        }
        $object->setStartDateIsSet($data['start_date_is_set'] ?? true);
        $object->setEndDateIsSet($data['end_date_is_set'] ?? true);
    }

    /**
     * Returns a valid unix timestamp or false
     *
     * @param string|mixed $dateValue
     * @return false|int
     */
    protected function strToTime($dateValue)
    {
        if (!empty($dateValue) && ($tstamp = strtotime((string) $dateValue)) && $tstamp <= DataParser::UNIX_TIMESTAMP_MAX) {
            return $tstamp;
        }
        return false;
    }

}
