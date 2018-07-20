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
 * @since     2018-07-10
 */

namespace BrainAppeal\BrainEventConnector\Converter;


use BrainAppeal\BrainEventConnector\Domain\Model\Event;
use BrainAppeal\BrainEventConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\BrainEventConnector\Domain\Repository\AbstractImportedRepository;
use BrainAppeal\BrainEventConnector\Domain\Repository\EventRepository;
use BrainAppeal\BrainEventConnector\Domain\Model\Convert2ObjectConfiguration;

abstract class AbstractEventToObjectConverter implements EventConverterInterface
{
    /**
     * @var AbstractImportedRepository
     */
    private $objectRepository;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var string
     */
    private $importSource;

    /**
     * @var Convert2ObjectConfiguration
     */
    private $configuration;

    /**
     * @return string
     */
    protected abstract function getObjectRepositoryClass();

    /**
     * @return AbstractImportedRepository
     */
    private function getObjectRepository()
    {
        if (null === $this->objectRepository) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
            $this->objectRepository = $objectManager->get($this->getObjectRepositoryClass());
        }

        return $this->objectRepository;
    }
    /**
     * @return EventRepository
     */
    private function getEventRepository()
    {
        if (null === $this->eventRepository) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
            $this->eventRepository = $objectManager->get(EventRepository::class);
        }

        return $this->eventRepository;
    }


    /**
     * @param EventRepository $eventRepository
     * @param Convert2ObjectConfiguration $configuration
     * @return Event[]
     */
    protected abstract function getMatchingEventsByConfiguration($eventRepository, $configuration);

    /**
     * @param Convert2ObjectConfiguration $configuration
     */
    private function setUp($configuration)
    {
        $dataMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->importSource = $dataMapper->getDataMap(get_class($configuration))->getTableName() . ':' . $configuration->getUid();
        $this->configuration = $configuration;
    }

    /**
     * @param Convert2ObjectConfiguration $configuration
     */
    public function run($configuration)
    {
        $this->setUp($configuration);

        $events = $this->getMatchingEventsByConfiguration($this->getEventRepository(), $this->configuration);

        $timestamp = time();
        foreach ($events as $event) {
            $this->convertEvent($event);
        }

        $objectRepository = $this->getObjectRepository();
        $objectRepository->persistAll();

        $results = $objectRepository->findByNotImportedSince($timestamp, $this->importSource, null);
        foreach ($results as $result) {
            $objectRepository->remove($result);
        }
        $objectRepository->persistAll();
    }

    /**
     * @param ImportedModelInterface $object
     * @param Event $event
     * @param Convert2ObjectConfiguration $configuration
     * @api Use this method to individualize your object
     */
    protected abstract function individualizeObjectByEvent(&$object, $event, $configuration);

    /**
     * @param \BrainAppeal\BrainEventConnector\Domain\Model\Event $event
     * @return void
     */
    private function convertEvent($event)
    {
        $configuration = $this->configuration;


        $importSource = $this->importSource;
        $importId = $event->getUid();

        $objectRepository = $this->getObjectRepository();
        /** @var ImportedModelInterface $object */
        $object = $objectRepository->findByImportOrCreate($importSource, $importId);

        $this->individualizeObjectByEvent($object, $event, $configuration);

        $object->setPid($configuration->getTargetPid());
        $object->setImportedAt(time());
        if ($object->getUid() > 0) {
            $objectRepository->update($object);
        } else {
            $objectRepository->add($object);
        }
    }
}