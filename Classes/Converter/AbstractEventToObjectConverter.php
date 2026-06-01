<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

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

    public function __construct(
        protected DataMapper $dataMapper,
        protected readonly EventRepository $eventRepository
    ) {
    }

    /**
     * @return AbstractImportedRepository
     */
    protected function getObjectRepository(): AbstractImportedRepository
    {
        if ($this->objectRepository === null) {
            throw new \InvalidArgumentException('Inject objectRepository in service constructor!', 6861810020);
        }

        return $this->objectRepository;
    }

    abstract protected function getTargetTable(): string;

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
     * @return Event[]|QueryResultInterface<int, Event>
     */
    abstract protected function getMatchingEventsByConfiguration(EventRepository $eventRepository, ConvertConfiguration $configuration): array|QueryResultInterface;

    /**
     * @param ConvertConfiguration $configuration
     */
    private function setUp(ConvertConfiguration $configuration): void
    {
        $this->importSource = $this->dataMapper->getDataMap($configuration::class)->getTableName() . ':' . $configuration->getUid();
        $this->configuration = $configuration;
        // Set the current language to "de" so news description translations are german
        if (($languageService = $this->getLanguageService()) instanceof LanguageService) {
            $languageService->lang = 'de';
        }
    }

    /**
     * @param ConvertConfiguration $configuration
     */
    public function run($configuration): void
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
    abstract protected function individualizeObjectByEvent(ImportedModelInterface $object, Event $event, ConvertConfiguration $configuration): void;

    /**
     * Returns true if the event can be converted to the target object model; Override this function in custom
     * converter to support skipping import of single events
     * @param Event $event
     * @return bool
     */
    protected function isConversionPossible(Event $event): bool
    {
        return true;
    }

    /**
     * @param Event $event
     */
    private function convertEvent(Event $event): void
    {
        $configuration = $this->configuration;

        $importSource = $this->importSource;
        $importId = $event->getUid();

        $objectRepository = $this->getObjectRepository();
        $object = $objectRepository->findByImport($importSource, $importId);
        if (!$object instanceof ImportedModelInterface) {
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
     * @return ?LanguageService
     */
    protected function getLanguageService(): ?LanguageService
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
        $importTable = $this->getTargetTable();
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
        if (!$object instanceof ImportedModelInterface) {
            $object = $objectRepository->createNewModelInstance($importSource, $importId, $pid);
        }
        return $object;
    }

    /**
     * @param Event $event
     * @return array
     */
    protected function getAdditionDataHandlerValues(Event $event): array
    {
        return [];
    }
}
