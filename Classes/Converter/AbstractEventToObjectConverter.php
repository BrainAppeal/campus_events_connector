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


use BrainAppeal\CampusEventsConnector\Domain\Model\ConvertConfiguration;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use BrainAppeal\CampusEventsConnector\Domain\Repository\EventRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
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
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
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
        //$dataMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $dataMapper = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $this->importSource = $dataMapper->getDataMap(get_class($configuration))->getTableName() . ':' . $configuration->getUid();
        $this->configuration = $configuration;
        // Set current language to "de" so news description translations are german
        if (null !== $languageService = $this->getLanguageService()) {
            $languageService->lang = 'de';
        }
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
            if ($this->isConversionPossible($event)) {
                $this->convertEvent($event);
            }
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
    protected abstract function individualizeObjectByEvent($object, $event, $configuration);

    /**
     * Returns true, if the event can be converted to the target object model; Override this function in custom
     * converter to support skipping import of single events
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $event
     * @return bool
     */
    protected function isConversionPossible($event)
    {
        return true;
    }

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
        $object = $objectRepository->findByImport($importSource, $importId);
        if (null === $object) {
            // Use DataHandler to prevent problems with news proxy classes (e.g. EXT:yoast_news defines a news model constructor, which is not valid)
            $object = $this->createNewModelInstance($event, $configuration->getTargetPid());
        }

        if ($object instanceof ImportedModelInterface) {
            $this->individualizeObjectByEvent($object, $event, $configuration);

            $object->setCeImportedAt(time());
            if ($object->getUid() > 0) {
                $objectRepository->update($object);
            } else {
                $objectRepository->add($object);
            }
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): \TYPO3\CMS\Core\Localization\LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $event
     * @param int $pid The target page id for storing the records
     * @return ImportedModelInterface
     */
    protected function createNewModelInstance($event, $pid)
    {
        $object = null;
        $objectRepository = $this->getObjectRepository();
        $importTable = $objectRepository->getImportTableName();
        $importSource = $this->importSource;
        $importId = $event->getUid();
        if ($importTable && isset($GLOBALS['TCA'][$importTable])) {
            $newIdPrefix = 'NEW123456';
            $saveId = $newIdPrefix . '0';
            $importData = array_merge($this->getAdditionDataHandlerValues($event), [
                'pid' => $pid,
                'ce_import_source' => $importSource,
                'ce_import_id' => $importId,
            ]);
            $tcaColumns = array_keys($GLOBALS['TCA'][$importTable]['columns']);
            $dataColumns = array_keys($importData);
            foreach ($dataColumns as $column) {
                if (!in_array($column, $tcaColumns, false)) {
                    unset($importData[$column]);
                }
            }

            $data = [
                $importTable => [
                    $saveId => $importData,
                ],
            ];
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($data, []);
            $dataHandler->enableLogging = false;
            $dataHandler->process_datamap();
            $object = $objectRepository->findByImport($importSource, $importId);
        }
        if (null === $object) {
            $object = $objectRepository->createNewModelInstance($importSource, $importId, $pid);
        }
        return $object;
    }

    /**
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\Event $event
     * @return array
     */
    protected function getAdditionDataHandlerValues($event)
    {
        $data = [];
        return $data;
    }
}
