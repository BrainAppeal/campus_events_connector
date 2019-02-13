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


namespace BrainAppeal\CampusEventsConnector\Converter;


use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository;
use BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration;

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
     * @var ConvertConfiguration
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
     * @param ConvertConfiguration $configuration
     * @return Event[]
     */
    protected abstract function getMatchingEventsByConfiguration($eventRepository, $configuration);

    /**
     * @param ConvertConfiguration $configuration
     */
    private function setUp($configuration)
    {
        $dataMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->importSource = $dataMapper->getDataMap(get_class($configuration))->getTableName() . ':' . $configuration->getUid();
        $this->configuration = $configuration;
    }

    /**
     * @param ConvertConfiguration $configuration
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
     * @param ConvertConfiguration $configuration
     * @api Use this method to individualize your object
     */
    protected abstract function individualizeObjectByEvent(&$object, $event, $configuration);

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $event
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
