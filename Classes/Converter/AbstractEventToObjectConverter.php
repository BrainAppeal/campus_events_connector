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
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

abstract class AbstractEventToObjectConverter implements EventConverterInterface
{
    /**
     * @var AbstractImportedRepository
     */
    protected $objectRepository;

    /**
     * @var string
     */
    private $importSource;

    /**
     * @var ConvertConfiguration
     */
    private $configuration;

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    public function __construct(
        DataMapper      $dataMapper,
        private readonly EventRepository $eventRepository)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @return AbstractImportedRepository
     */
    protected function getObjectRepository(): AbstractImportedRepository
    {
        if (null === $this->objectRepository) {
            throw new \InvalidArgumentException('Inject objectRepository in service constructor!');
        }

        return $this->objectRepository;
    }

    /**
     * @return EventRepository
     */
    private function getEventRepository(): EventRepository
    {
        return $this->eventRepository;
    }


    /**
     * @param EventRepository $eventRepository
     * @param ConvertConfiguration $configuration
     * @return Event[]
     */
    protected abstract function getMatchingEventsByConfiguration(EventRepository $eventRepository, ConvertConfiguration $configuration);

    /**
     * @param ConvertConfiguration $configuration
     */
    private function setUp(ConvertConfiguration $configuration): void
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $this->importSource = $dataMapper->getDataMap($configuration::class)->getTableName() . ':' . $configuration->getUid();
        $this->configuration = $configuration;
        // Set the current language to "de" so news description translations are german
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
    protected abstract function individualizeObjectByEvent($object, $event, $configuration): void;

    /**
     * Returns true, if the event can be converted to the target object model; Override this function in custom
     * converter to support skipping import of single events
     * @param Event $event
     * @return bool
     */
    protected function isConversionPossible($event)
    {
        return true;
    }

    /**
     * @param Event $event
     * @return void
     */
    private function convertEvent(Event $event): void
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

        $this->individualizeObjectByEvent($object, $event, $configuration);

        $object->setCeImportedAt(time());
        if ($object->getUid() > 0) {
            $objectRepository->update($object);
        } else {
            $objectRepository->add($object);
        }
    }

    /**
     * @return ?\TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): ?\TYPO3\CMS\Core\Localization\LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    /**
     * @param Event $event
     * @param int $pid The target page id for storing the records
     * @return ImportedModelInterface
     */
    protected function createNewModelInstance(Event $event, int $pid): ImportedModelInterface
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
            /** @var DataHandler $dataHandler */
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
     * @param Event $event
     * @return array
     */
    protected function getAdditionDataHandlerValues($event)
    {
        return [];
    }
}
