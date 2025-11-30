<?php

declare(strict_types=1);

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

use BrainAppeal\CampusEventsConnector\Domain\Model\BelongsToEventInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedApiConnector;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedFileImporter;
use BrainAppeal\CampusEventsConnector\Importer\ImportMappingModel;
use BrainAppeal\CampusEventsConnector\Utility\ImportScheduleUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class ExtendedImportObjectGenerator implements SingletonInterface
{

    protected ?string $baseUri = null;

    /**
     * @var int
     */
    private $pid;

    private ?DBALInterface $dbal = null;

    /**
     * Local cache for imported items
     * @var array
     */
    protected $importItems = [];

    /**
     * Local storage for extbase instances
     * @var array<string, array<int, ImportMappingModel>>
     */
    protected $groupedImportMappingModels = [];

    /**
     * @var array
     */
    protected $dataMap;

    /**
     * @var ExtendedFileImporter
     */
    protected $fileImporter;


    /**
     * @return DBALInterface
     */
    private function getDBAL(): DBALInterface
    {
        if (null === $this->dbal) {
            $this->dbal = DBALFactory::getInstance();
        }

        return $this->dbal;
    }

    /**
     * @param array $dataMap
     * @param string $importSource
     * @param int $pid
     * @param ExtendedFileImporter $fileImporter
     * @param bool|string $debug
     * @return array<string, array<int, ImportMappingModel>>
     */
    public function processQueue(array $dataMap, string $importSource, int $pid, ExtendedFileImporter $fileImporter, bool|string $debug = false): array
    {
        $this->fileImporter = $fileImporter;
        $this->dataMap = $dataMap;
        $this->baseUri = $importSource;
        $this->pid = $pid;
        $this->groupedImportMappingModels = [];
        /** @var ImportScheduleUtility $importScheduleUtility */
        $importScheduleUtility = GeneralUtility::makeInstance(ImportScheduleUtility::class);
        $queueList = $importScheduleUtility->fetchScheduleEntries();
        $groupedImportList = [];
        // First, build a mapping for all import items (needed later for model references)
        foreach ($queueList as $queueItem) {
            $importType = $queueItem['import_type'];
            $importId = (int)$queueItem['import_uid'];
            $mappingModel = new ImportMappingModel($importId, $importType, $importSource, $queueItem);
            $this->groupedImportMappingModels[$importType][$importId] = $mappingModel;
            $groupedImportList[$importType][] = $importId;
        }
        foreach ($groupedImportList as $importType => $importTypeIdList) {
            foreach ($importTypeIdList as $importId) {
                $importMappingModel = $this->getImportMappingModel($importId, $importType);
                $this->assignClassSpecificProperties($importMappingModel);
                if ($queueItemId = $importMappingModel->getQueueItemUid()) {
                    $importScheduleUtility->finishScheduleEntryAsImported(
                        $queueItemId,
                        (int)$importMappingModel->getTargetModelUid(),
                        (bool)$debug
                    );
                }
            }
        }
        return $this->groupedImportMappingModels;
    }

    /**
     * Returns the import mapping model for the given import id and type;
     * the import model contains the domain model!
     *
     * @param int $importId
     * @param string $importType
     * @return ImportMappingModel
     */
    protected function getImportMappingModel(int $importId, string $importType): ImportMappingModel
    {
        if (!isset($this->groupedImportMappingModels[$importType][$importId])) {
            $mappingModel = new ImportMappingModel($importId, $importType, $this->baseUri, null);
            $this->groupedImportMappingModels[$importType][$importId] = $mappingModel;
        } else {
            $mappingModel = $this->groupedImportMappingModels[$importType][$importId];
        }
        if ($mappingModel->getDomainModel() === null) {
            $dataTypeMap = $this->dataMap[$importType];
            $class = $dataTypeMap['class'];
            $domainModel = $this->getDBAL()->findByImport($class, $this->baseUri, $importId, $this->pid);
            if ($domainModel === null) {
                /** @var ImportedModelInterface $domainModel */
                $domainModel = GeneralUtility::makeInstance($class);
                $domainModel->setCeImportId($importId);
                $domainModel->setCeImportSource($this->baseUri);
                $domainModel->setPid($this->pid);
            }
            $mappingModel->setDomainModel($domainModel);
        }
        return $mappingModel;
    }

    /**
     * Returns the import mapping model for the given model reference data
     *
     * @param array<string, mixed> $referenceData Model data referenced by another model
     * @return ImportMappingModel
     */
    protected function getImportMappingModelByReference(array $referenceData): ImportMappingModel
    {
        $importType = $referenceData['@type'];
        $importId = ExtendedApiConnector::filterId($referenceData['@id'], $importType);
        return $this->getImportMappingModel($importId, $importType);
    }

    /**
     * @param ImportMappingModel $importMappingModel
     */
    protected function assignClassSpecificProperties(ImportMappingModel $importMappingModel): void
    {
        $domainModel = $importMappingModel->getDomainModel();
        $importType = $importMappingModel->getImportType();
        $importId = $importMappingModel->getImportId();
        if (!($domainModel instanceof ImportedModelInterface) || !$importMappingModel->existsInApi()) {
            return;
        }
        $domainModel->setCeImportedAt(time());
        $domainModel->setCeImportId($importId);
        switch ($importType) {
            case 'Category':
                $this->assignCategoryProperties($importMappingModel);
                break;

            case 'ContactPerson':
                $this->assignContactPersonProperties($importMappingModel);
                break;

            case 'Event':
                $this->assignEventProperties($importMappingModel);
                break;

            case 'EventAttachment':
                $this->assignEventAttachmentProperties($importMappingModel);
                break;

            case 'EventImage':
                $this->assignEventImageProperties($importMappingModel);
                break;

            case 'EventSession':
                $this->assignEventSessionProperties($importMappingModel);
                break;

            case 'EventTicketPriceVariant':
                $this->assignEventTicketPriceVariantProperties($importMappingModel);
                break;

            case 'FilterCategory':
                $this->assignFilterCategoryProperties($importMappingModel);
                break;

            case 'Location':
                $this->assignLocationProperties($importMappingModel);
                break;

            case 'Organizer':
                $this->assignOrganizerProperties($importMappingModel);
                break;

            case 'PriceCategory':
                $this->assignPriceCategoryProperties($importMappingModel);
                break;

            case 'Referent':
                $this->assignReferentProperties($importMappingModel);
                break;

            case 'SessionTimePeriod':
                $this->assignTimeRangeProperties($importMappingModel);
                break;

            case 'Sponsor':
                $this->assignSponsorProperties($importMappingModel);
                break;

            case 'TargetGroup':
                $this->assignTargetGroupProperties($importMappingModel);
                break;

            case 'ViewList':
                $this->assignViewListProperties($importMappingModel);
                break;
        }
    }

    /**
     * Process referenced objects
     *
     * @param ImportedModelInterface $object The domain model object
     * @param array $referencesFromApi The references found through the API
     * @param string $objectProperty The name of the object property, the references are assigned to
     * @param string|null $objectPropertyPlural The plural name of the object property (Default: $objectProperty + 's')
     * @param string|null $referencingProperty Optional property in the reference object for assigning the domain model
     */
    protected function processReferencesMultiple(
        ImportedModelInterface $object,
        array                  $referencesFromApi,
                               $objectProperty,
                               $objectPropertyPlural = null,
                               $referencingProperty = null
    )
    {
        if (empty($objectPropertyPlural)) {
            $objectPropertyPlural = $objectProperty . 's';
        }
        $getter = 'get' . ucfirst($objectPropertyPlural);
        $addFunction = 'add' . ucfirst($objectProperty);
        $removeFunction = 'remove' . ucfirst($objectProperty);
        $mapPreviouslyImportedReferences = [];
        $existingReferences = $object->$getter();
        foreach ($existingReferences as $reference) {
            if (($reference instanceof ImportedModelInterface) && $refUid = $reference->getUid()) {
                $mapPreviouslyImportedReferences[$refUid] = $reference;
            }
        }
        $mapExistingReferences = [];
        foreach ($referencesFromApi as $referenceData) {
            $refImportMappingModel = $this->getImportMappingModelByReference($referenceData);
            $refDomainModel = $refImportMappingModel->getDomainModel();
            if (($refDomainModel instanceof ImportedModelInterface)) {
                $refUid = $refDomainModel->getUid();
                $refImportKey = (string) $refDomainModel->getCeImportSource() . ':' . $refDomainModel->getCeImportId();
                if ($refUid && array_key_exists($refUid, $mapPreviouslyImportedReferences)) {
                    unset($mapPreviouslyImportedReferences[$refUid]);
                    $mapExistingReferences[$refUid] = $refDomainModel;
                // Prevent duplicate assignment of the same object
                } elseif (!$refUid || !isset($mapExistingReferences[$refImportKey])) {
                    if (null === $referencingProperty
                        && $object instanceof Event
                        && $refDomainModel instanceof BelongsToEventInterface) {
                        $refDomainModel->setEvent($object);
                    } elseif (null !== $referencingProperty) {
                        $refSetter = 'set' . ucfirst($referencingProperty);
                        $refDomainModel->$refSetter($object);
                    }
                    $object->$addFunction($refDomainModel);
                    $mapExistingReferences[$refImportKey] = $refDomainModel;
                }
            }
        }
        foreach ($mapPreviouslyImportedReferences as $modelReferenceToRemove) {
            $object->$removeFunction($modelReferenceToRemove);
        }
    }

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignCategoryProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignFilterCategoryProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignLocationProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignOrganizerProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignTargetGroupProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignViewListProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignContactPersonProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventAttachmentProperties(ImportMappingModel $importMappingModel): void;

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventImageProperties(ImportMappingModel $importMappingModel): void;

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventSessionProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventTicketPriceVariantProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignPriceCategoryProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignReferentProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignTimeRangeProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignSponsorProperties(ImportMappingModel $importMappingModel): void;

}
