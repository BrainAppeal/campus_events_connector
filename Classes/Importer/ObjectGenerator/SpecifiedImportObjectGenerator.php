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
use BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\Location;
use BrainAppeal\CampusEventsConnector\Domain\Model\Organizer;
use BrainAppeal\CampusEventsConnector\Domain\Model\Speaker;
use BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup;
use BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange;
use BrainAppeal\CampusEventsConnector\Importer\FileImporter;

class SpecifiedImportObjectGenerator extends ImportObjectGenerator
{

    /**
     * @inheritdoc
     */
    protected function assignCategoryProperties($class, $object, $data)
    {
        $object->setName($data['name']);
    }

    /**
     * @inheritdoc
     */
    protected function assignEventProperties($class, $object, $data)
    {
        if (empty($object->getHash()) || $object->getHash() != $data['hash']) {
            $this->setDataChanged();
        }

        $object->setName($data['name']);
        $object->setCanceled($data['canceled']);
        $object->setUrl($data['url'] ?? '');
        $object->setSubtitle($data['subtitle']);
        $object->setDescription($data['description']);
        $object->setShortDescription($data['short_description']);
        $object->setShowInNews($data['show_in_news']);
        $object->setNewsText($data['news_text']);
        $object->setStatus($data['status']['id']);
        $object->setLearningObjective($data['learning_objective']);
        $object->setRegistrationPossible($data['registration_possible']);
        $object->setMinParticipants($data['min_participants']);
        $object->setMaxParticipants($data['max_participants']);
        $object->setParticipants($data['participants']);
        $object->setHash($data['hash']);

        /** @var TimeRange[] $timeRanges */
        $timeRanges = $this->generateMultiple(TimeRange::class, $data['timeranges']);
        foreach ($timeRanges as $timeRange) {
            $object->addTimeRange($timeRange);
        }

        /** @var Speaker[] $speakers */
        $speakers = $this->generateMultiple(Speaker::class, $data['referents']);
        foreach ($speakers as $speaker) {
            $object->addSpeaker($speaker);
        }

        /** @var Organizer[] $organizers */
        $organizers = $this->generateMultiple(Organizer::class, $data['organizer']);
        foreach ($organizers as $organizer) {
            $object->addOrganizer($organizer);
        }

        /** @var TargetGroup[] $targetGroups */
        $targetGroups = $this->generateMultiple(TargetGroup::class, $data['target_groups']);
        foreach ($targetGroups as $targetGroup) {
            $object->addTargetGroup($targetGroup);
        }

        /** @var FilterCategory[] $filterCategories */
        $filterCategories = $this->generateMultiple(FilterCategory::class, $data['filter_categories']);
        foreach ($filterCategories as $filterCategory) {
            $object->addFilterCategory($filterCategory);
        }

        /** @var Category[] $categories */
        $categories = $this->generateMultiple(Category::class, $data['categories']);
        foreach ($categories as $category) {
            $object->addCategory($category);
        }

        /** @var Location $location */
        $location = $this->generate(Location::class, $data['location']['id'], $data['location']);
        $object->setLocation($location);

        /** @var FileImporter $fileImporter */
        $fileImporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FileImporter::class);
        foreach ($data['images'] as $attachmentData) {
            $fileImporter->enqueueFileMapping($object, 'images', $attachmentData);
        }

        /** @var FileImporter $fileImporter */
        $fileImporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FileImporter::class);
        foreach ($data['attachments'] as $attachmentData) {
            $fileImporter->enqueueFileMapping($object, 'attachments', $attachmentData);
        }
    }

    /**
     * @inheritdoc
     */
    protected function assignFilterCategoryProperties($class, $object, $data)
    {
        $object->setName($data['name']);
        $object->setParent($this->generate($class, $data['parent_id'], null));
    }

    /**
     * @inheritdoc
     */
    protected function assignLocationProperties($class, $object, $data)
    {
        $object->setName($data['name']);
        $object->setStreetName($data['street_name'] ?? '');
        $object->setTown($data['town'] ?? '');
        $object->setZipCode($data['zip_code'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignOrganizerProperties($class, $object, $data)
    {
        $object->setName($data['name']);
    }

    /**
     * @inheritdoc
     */
    protected function assignSpeakerProperties($class, $object, $data)
    {
        $object->setTitle($data['title'] ?? '');
        $object->setFirstName($data['first_name'] ?? '');
        $object->setLastName($data['last_name'] ?? '');
    }

    /**
     * @inheritdoc
     */
    protected function assignTargetGroupProperties($class, $object, $data)
    {
        $object->setName($data['name']);
    }

    /**
     * @inheritdoc
     */
    protected function assignTimeRangeProperties($class, $object, $data)
    {
        if ($tstamp = $this->strToTime($data['end_date'])) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $this->strToTime($data['start_date'])) {
            $object->setStartTstamp($tstamp);
        }
        $object->setStartDateIsSet(isset($data['start_date_is_set']) ? $data['start_date_is_set'] : true);
        $object->setEndDateIsSet(isset($data['end_date_is_set']) ? $data['end_date_is_set'] : true);
    }

    /**
     * Returns a valid unix timestamp or false
     *
     * @param string|mixed $dateValue
     * @return false|int
     */
    protected function strToTime($dateValue)
    {
        if (($tstamp = strtotime($dateValue)) && $tstamp <= self::UNIX_TIMESTAMP_MAX) {
            return $tstamp;
        }
        return false;
    }

}
