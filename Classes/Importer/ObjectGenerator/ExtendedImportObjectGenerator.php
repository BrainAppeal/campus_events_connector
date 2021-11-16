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

use BrainAppeal\CampusEventsConnector\Domain\Model\BelongsToEventInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedApiConnector;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedFileImporter;
use BrainAppeal\CampusEventsConnector\Importer\ImportMappingModel;
use BrainAppeal\CampusEventsConnector\Utility\ImportScheduleUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class ExtendedImportObjectGenerator implements SingletonInterface
{
    /**
     * @var string
     */
    private $importSource;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var DBALInterface
     */
    private $dbal;

    /**
     * Local cache for imported items
     * @var array
     */
    protected $importItems = [];

    /**
     * Local storage for extbase instances
     * @var array
     */
    protected $importMappingObjectStorage = [];

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
    private function getDBAL()
    {
        if (null === $this->dbal) {
            $this->dbal = \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory::getInstance();
        }

        return $this->dbal;
    }

    /**
     * @param array $dataMap
     * @param string $importSource
     * @param int $pid
     * @param false $debug
     * @return array|ImportedModelInterface[]
     */
    public function processQueue(array $dataMap, $importSource, $pid, ExtendedFileImporter $fileImporter, $debug = false)
    {
        $this->fileImporter = $fileImporter;
        $this->dataMap = $dataMap;
        $this->importSource = $importSource;
        $this->pid = (int)$pid;
        $this->importMappingObjectStorage = [];
        /** @var ImportScheduleUtility $importScheduleUtility */
        $importScheduleUtility = GeneralUtility::makeInstance(ImportScheduleUtility::class);
        $queueList = $importScheduleUtility->fetchScheduleEntries();
        $groupedImportList = [];
        // First build a mapping for all import items (needed later for model references)
        foreach ($queueList as $queueItem) {
            $importType = $queueItem['import_type'];
            $importId = (int)$queueItem['import_uid'];
            $mappingModel = new ImportMappingModel($importId, $importType, $queueItem);
            $this->importMappingObjectStorage[$importType][$importId] = $mappingModel;
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
                        $debug
                    );
                }
            }
        }
        $updatedDomainModels = [];
        foreach ($this->importMappingObjectStorage as $importModelsForType) {
            /** @var ImportMappingModel $importMappingModel */
            foreach ($importModelsForType as $importMappingModel) {
                if (null !== $domainModel = $importMappingModel->getDomainModel()) {
                    $updatedDomainModels[] = $domainModel;
                }
            }

        }
        return $updatedDomainModels;
    }

    /**
     * Returns the import mapping model for the given import id and type;
     * the import model contains the domain model!
     *
     * @param int $importId
     * @param string $importType
     * @return ImportMappingModel
     */
    protected function getImportMappingModel($importId, $importType): ImportMappingModel
    {
        if (!isset($this->importMappingObjectStorage[$importType][$importId])) {
            $mappingModel = new ImportMappingModel($importId, $importType, null);
            $this->importMappingObjectStorage[$importType][$importId] = $mappingModel;
        } else {
            $mappingModel = $this->importMappingObjectStorage[$importType][$importId];
        }
        if (null === $mappingModel->getDomainModel()) {
            $dataTypeMap = $this->dataMap[$importType];
            $class = $dataTypeMap['class'];
            $domainModel = $this->getDBAL()->findByImport($class, $this->importSource, $importId, $this->pid);
            if (null === $domainModel) {
                /** @var ImportedModelInterface $object */
                $domainModel = new $class;
                $domainModel->setCeImportId($importId);
                $domainModel->setCeImportSource($this->importSource);
                $domainModel->setPid($this->pid);
            }
            $mappingModel->setDomainModel($domainModel);
        }
        return $mappingModel;
    }

    /**
     * Returns the import mapping model for the given model reference data
     *
     * @param array $referenceData Model data referenced by another model
     * @return ImportMappingModel
     */
    protected function getImportMappingModelByReference($referenceData): ImportMappingModel
    {
        $importType = $referenceData['@type'];
        $importId = ExtendedApiConnector::filterId($referenceData['@id'], $importType);
        return $this->getImportMappingModel($importId, $importType);
    }

    /**
     * @param ImportMappingModel $importMappingModel
     */
    protected function assignClassSpecificProperties(ImportMappingModel $importMappingModel)
    {
        $domainModel = $importMappingModel->getDomainModel();
        if (!($domainModel instanceof ImportedModelInterface) || !$importMappingModel->existsInApi()) {
            return;
        }
        $importType = $importMappingModel->getImportType();
        $importId = $importMappingModel->getImportId();
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
        $mapExistingReferences = [];
        $existingReferences = $object->$getter();
        foreach ($existingReferences as $reference) {
            if (($reference instanceof ImportedModelInterface) && $refUid = $reference->getUid()) {
                $mapExistingReferences[$refUid] = $reference;
            }
        }
        foreach ($referencesFromApi as $referenceData) {
            $refImportMappingModel = $this->getImportMappingModelByReference($referenceData);
            $refDomainModel = $refImportMappingModel->getDomainModel();
            if (($refDomainModel instanceof ImportedModelInterface)) {
                $refUid = $refDomainModel->getUid();
                if ($refUid && array_key_exists($refUid, $mapExistingReferences)) {
                    unset($mapExistingReferences[$refUid]);
                } else {
                    if (null === $referencingProperty
                        && $object instanceof Event
                        && $refDomainModel instanceof BelongsToEventInterface) {
                        $refDomainModel->setEvent($object);
                    } elseif (null !== $referencingProperty) {
                        $refSetter = 'set' . ucfirst($referencingProperty);
                        $refDomainModel->$refSetter($object);
                    }
                    $object->$addFunction($refDomainModel);
                }
            }
        }
        foreach ($mapExistingReferences as $modelReferenceToRemove) {
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
    abstract protected function assignContactPersonProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventAttachmentProperties(ImportMappingModel $importMappingModel);

    /**
     * @param ImportMappingModel $importMappingModel
     */
    abstract protected function assignEventImageProperties(ImportMappingModel $importMappingModel);

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
    abstract protected function assignSponsorProperties(ImportMappingModel $importMappingModel);

}
