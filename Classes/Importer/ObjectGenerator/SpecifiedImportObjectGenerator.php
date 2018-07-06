<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-03
 */

namespace BrainAppeal\BrainEventConnector\Importer\ObjectGenerator;


use BrainAppeal\BrainEventConnector\Domain\Model\Category;
use BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory;
use BrainAppeal\BrainEventConnector\Domain\Model\Location;
use BrainAppeal\BrainEventConnector\Domain\Model\Organizer;
use BrainAppeal\BrainEventConnector\Domain\Model\Speaker;
use BrainAppeal\BrainEventConnector\Domain\Model\TargetGroup;
use BrainAppeal\BrainEventConnector\Domain\Model\TimeRange;
use BrainAppeal\BrainEventConnector\Importer\FileImporter;

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
        $object->setName($data['name']);
        $object->setCanceled($data['canceled']);
        $object->setUrl($data['url']);
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
        $object->setStreetName($data['street_name']);
        $object->setTown($data['town']);
        $object->setZipCode($data['zip_code']);
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
        $object->setTitle($data['title']);
        $object->setFirstName($data['first_name']);
        $object->setLastName($data['last_name']);
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
        $object->setStartDate(new \DateTime($data['start_date']));
        $object->setEndDate(new \DateTime($data['end_date']));
    }

}