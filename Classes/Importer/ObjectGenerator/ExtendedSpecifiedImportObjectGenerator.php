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
use BrainAppeal\CampusEventsConnector\Domain\Model\Category;
use BrainAppeal\CampusEventsConnector\Domain\Model\ContactPerson;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\EventAttachment;
use BrainAppeal\CampusEventsConnector\Domain\Model\EventImage;
use BrainAppeal\CampusEventsConnector\Domain\Model\EventSession;
use BrainAppeal\CampusEventsConnector\Domain\Model\EventTicketPriceVariant;
use BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\Location;
use BrainAppeal\CampusEventsConnector\Domain\Model\Organizer;
use BrainAppeal\CampusEventsConnector\Domain\Model\PriceCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\Referent;
use BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor;
use BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup;
use BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange;
use BrainAppeal\CampusEventsConnector\Domain\Model\ViewList;
use BrainAppeal\CampusEventsConnector\Importer\ImportMappingModel;
use BrainAppeal\CampusEventsConnector\Utility\DataParser;
use InvalidArgumentException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ExtendedSpecifiedImportObjectGenerator extends ExtendedImportObjectGenerator
{

    /**
     * @inheritdoc
     */
    protected function assignCategoryProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Category) || empty($data)) {
            return;
        }
        $object->setName($data['name']);
    }

    /**
     * @inheritdoc
     */
    protected function assignEventProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Event) || empty($data)) {
            return;
        }
        $parser = new DataParser();
        $tstampModifiedAt = (int)$parser->strToTime($data['modifiedAtRecursive'] ?? null);
        $object->setName($data['name'] ?? '');
        $urls = [];
        if (array_key_exists('@urls', $data)) {
            $urls = $data['@urls'];
        }
        if (empty($urls['eventUrl']) && !empty($this->dbImportSource)) {
            $baseUri = $this->dbImportSource;
            if (!str_starts_with($baseUri, 'http')) {
                $baseUri = 'https://' . $baseUri;
            }
            $urls['eventUrl'] = rtrim($baseUri, '/') . '/event/' . $importMappingModel->getImportId();
        }
        if (!$tstampModifiedAt && $tstampModifiedAt > $object->getModifiedAtRecursive()) {
            $object->setUrl($urls['eventUrl'] ?? '');
            $object->setSubtitle($data['subtitle'] ?? '');
            $parser = new DataParser();
            $object->setDescription($parser->cleanupHtmlForRTE((string)($data['description'] ?? '')));
            $object->setShortDescription($data['shortDescription'] ?? '');
            $object->setDisturberMessage($data['disturberMessage'] ?? '');
            $object->setEventAttendanceMode($data['eventAttendanceMode'] ?? '');
            $object->setEventNumber($data['eventNumber'] ?? '');
            $object->setExternalOrderEmailAddress($data['externalOrderEmailAddress'] ?? '');
            $object->setExternalOrderUrl($data['externalOrderUrl'] ?? '');
            $object->setDirectRegistrationUrl($urls['directRegistrationUrl'] ?? '');
            $object->setOrderType((int)($data['orderType'] ?? 0));
            $object->setReferentsTitle($data['referentsTitle'] ?? '');
            $object->setSeoTitle($data['seoTitle'] ?? '');
            $object->setSeoDescription($data['seoDescription'] ?? '');
            $object->setSponsorsTitle($data['sponsorsTitle'] ?? '');
            $object->setCanceled(!empty($data['canceled']));
            $object->setPublished(!empty($data['published']));
            $object->setArchived(!empty($data['completed']));
            $object->setCompleted(!empty($data['archived']));
            $object->setSeoRobotsIndex(($data['seoRobotsIndex'] ?? '') !== 'noindex');
            $object->setSeoRobotsFollow(($data['seoRobotsFollow'] ?? '') !== 'nofollow');
            $object->setLearningObjective($parser->cleanupHtmlForRTE((string)($data['learningObjective'] ?? '')));
            $object->setMinParticipants((int)($data['minParticipants'] ?? null));
            $object->setMaxParticipants((int)($data['maxParticipants'] ?? null));
            if ($tstamp = $parser->strToTime($data['startDate'] ?? null)) {
                $object->setStartTstamp($tstamp);
            }
            if ($tstamp = $parser->strToTime($data['endDate'] ?? null)) {
                $object->setEndTstamp($tstamp);
            }
        }
        if ($tstampModifiedAt > $object->getModifiedAtRecursive()) {
            $object->setModifiedAtRecursive($tstampModifiedAt);
        }

        if (!empty($data['organizers'])) {
            $this->processReferencesMultiple(
                $object,
                $data['organizers'],
                'organizer',
                'organizer'
            );
        }
        if (!empty($data['targetGroups'])) {
            $this->processReferencesMultiple(
                $object,
                $data['targetGroups'],
                'targetGroup'
            );
        }
        if (!empty($data['filterCategories'])) {
            $this->processReferencesMultiple(
                $object,
                $data['filterCategories'],
                'filterCategory',
                'filterCategories'
            );
        }
        if (!empty($data['viewLists'])) {
            $this->processReferencesMultiple(
                $object,
                $data['viewLists'],
                'viewList'
            );
        }
        if (!empty($data['categories'])) {
            $this->processReferencesMultiple(
                $object,
                $data['categories'],
                'category',
                'categories'
            );
        }
        if (!empty($data['alternativeEvents'])) {
            $this->processReferencesMultiple(
                $object,
                $data['alternativeEvents'],
                'alternativeEvent'
            );
        }
        if (!empty($data['contactPersons'])) {
            $this->processReferencesMultiple(
                $object,
                $data['contactPersons'],
                'contactPerson'
            );
        }
        if (!empty($data['attachments'])) {
            $this->processReferencesMultiple(
                $object,
                $data['attachments'],
                'eventAttachment'
            );
        }
        if (!empty($data['images'])) {
            $this->processReferencesMultiple(
                $object,
                $data['images'],
                'eventImage'
            );
        }
        if (!empty($data['eventSessions'])) {
            $this->processReferencesMultiple(
                $object,
                $data['eventSessions'],
                'eventSession'
            );
        }
        if (!empty($data['eventTicketPriceVariants'])) {
            $this->processReferencesMultiple(
                $object,
                $data['eventTicketPriceVariants'],
                'eventTicketPriceVariant'
            );
        }
        if (!empty($data['locations'])) {
            $locations = $data['locations'];
        } elseif (!empty($data['location'])) {
            // API returns only 1 location, but internally this is a ManyToMany reference
            $locations = [$data['location']];
        } else {
            $locations = null;
        }
        if (!empty($locations)) {
            $this->processReferencesMultiple(
                $object,
                $locations,
                'location'
            );
            $objectLocations = $object->getLocations();
            if ($objectLocations->count() > 0) {
                $object->setLocation($objectLocations->current());
            }
        } else {
            $object->setLocation(null);
        }
        if (!empty($data['referents'])) {
            $this->processReferencesMultiple(
                $object,
                $data['referents'],
                'referent'
            );
        }
        if (!empty($data['sponsors'])) {
            $this->processReferencesMultiple(
                $object,
                $data['sponsors'],
                'sponsor'
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function assignFilterCategoryProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof FilterCategory) || empty($data)) {
            return;
        }
        $object->setName($data['name']);
        if (!empty($data['parent'])) {
            $parentModel = $this->getImportMappingModelByReference($data['parent'])?->getDomainModel();
            if ($parentModel instanceof FilterCategory) {
                $object->setParent($parentModel);
            } else {
                $object->setParent(null);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function assignLocationProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Location) || empty($data)) {
            return;
        }
        $object->setName($data['name']);
        $object->setStreetName($this->cropFieldValue($data, 'streetName', 255));
        $object->setTown($this->cropFieldValue($data, 'town', 255));
        $object->setZipCode($this->cropFieldValue($data, 'zipCode', 255));
        $object->setBuilding($this->cropFieldValue($data, 'building', 255));
        $object->setLatitude($this->cropFieldValue($data, 'latitude', 255));
        $object->setListViewDisplayName($this->cropFieldValue($data, 'listViewDisplayName', 255));
        $object->setLongitude($this->cropFieldValue($data, 'longitude', 255));
        $object->setRoom($this->cropFieldValue($data, 'room', 255));
    }

    /**
     * @inheritdoc
     */
    protected function assignOrganizerProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Organizer) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
    }

    /**
     * @inheritdoc
     */
    protected function assignReferentProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Referent) || empty($data)) {
            return;
        }
        $object->setAcademicDegree($this->cropFieldValue($data, 'academicDegree', 255));
        $object->setBusinessAddress($this->cropFieldValue($data, 'businessAddress', 65535));
        $object->setDescription($this->cropFieldValue($data, 'description', 65535));
        $object->setEmail($this->cropFieldValue($data, 'email', 255));
        $object->setEventFormats($this->cropFieldValue($data, 'eventFormats', 65535));
        $object->setExternalUrl($this->cropFieldValue($data, 'externalUrl', 255));
        $object->setFirstName($this->cropFieldValue($data, 'firstName', 255));
        $object->setFocusOfWork($this->cropFieldValue($data, 'focusOfWork', 65535));
        $object->setInstitution($this->cropFieldValue($data, 'institution', 255));
        $object->setLastName($this->cropFieldValue($data, 'lastName', 255));
        $object->setPhone($this->cropFieldValue($data, 'phone', 255));
        $object->setPublications($this->cropFieldValue($data, 'publications', 65535));
        $object->setReferences($this->cropFieldValue($data, 'references', 65535));
        $object->setTitle($this->cropFieldValue($data, 'title', 255));
        $object->setType((int)($data['type'] ?? 0));
    }

    /**
     * Returns the string value of the given field; if the value is longer than the allowed length, the text is cropped
     * @param array $data
     * @param string $field
     * @param int $maxLength
     * @return string
     */
    private function cropFieldValue(array $data, string $field, int $maxLength): string
    {
        $text = $data[$field] ?? '';
        if ($maxLength > 0 && mb_strlen((string)$text) > $maxLength) {
            return mb_substr((string)$text, 0, $maxLength);
        }
        return $text;
    }

    /**
     * @inheritdoc
     */
    protected function assignTargetGroupProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof TargetGroup) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
    }

    /**
     * @inheritdoc
     */
    protected function assignViewListProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof ViewList) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
    }


    protected function assignContactPersonProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof ContactPerson) || empty($data)) {
            return;
        }
        $object->setDepartment($this->cropFieldValue($data, 'department', 255));
        $object->setFirstName($this->cropFieldValue($data, 'firstName', 255));
        $object->setInstitution($this->cropFieldValue($data, 'institution', 255));
        $object->setLastName($this->cropFieldValue($data, 'lastName', 255));
        $object->setMailAddress($this->cropFieldValue($data, 'mailAddress', 255));
        $object->setPhone($this->cropFieldValue($data, 'phone', 255));
        $object->setPosition($this->cropFieldValue($data, 'position', 255));
        $object->setTitle($this->cropFieldValue($data, 'title', 255));
    }

    protected function assignEventAttachmentProperties(ImportMappingModel $importMappingModel): void
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventAttachment) || empty($data)) {
            return;
        }
        $this->assignMandatoryEntityReference($importMappingModel);
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setFileHash($data['fileHash'] ?? '');
        if ($data['attachmentFile']['url'] ?? null) {
            $this->fileImporter->enqueueFileMapping($object, 'attachment_file', $data['attachmentFile']);
        } elseif ($object->getAttachmentFile()) {
            $this->fileImporter->deleteObsoleteFileReferenceOnImport($object->getAttachmentFile());
            $object->setAttachmentFile(null);
        }
    }

    protected function assignEventImageProperties(ImportMappingModel $importMappingModel): void
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventImage) || empty($data)) {
            return;
        }
        $this->assignMandatoryEntityReference($importMappingModel);
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setFileHash($data['fileHash'] ?? '');
        if ($data['imageFile']['url'] ?? null) {
            $this->fileImporter->enqueueFileMapping($object, 'image_file', $data['imageFile']);
        } elseif ($object->getImageFile()) {
            $this->fileImporter->deleteObsoleteFileReferenceOnImport($object->getImageFile());
            $object->setImageFile(null);
        }
    }

    protected function assignEventSessionProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventSession) || empty($data)) {
            return;
        }
        $this->assignMandatoryEntityReference($importMappingModel);
        $parser = new DataParser();
        if ($tstamp = $parser->strToTime($data['endDate'] ?? null)) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $parser->strToTime($data['startDate'] ?? null)) {
            $object->setStartTstamp($tstamp);
        }
        if (!empty($data['sessionDates'])) {
            $this->processReferencesMultiple(
                $object,
                $data['sessionDates'],
                'sessionTimePeriod',
                null,
                'eventSession'
            );
        }
    }

    protected function assignEventTicketPriceVariantProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventTicketPriceVariant) || empty($data)) {
            return;
        }
        $this->assignMandatoryEntityReference($importMappingModel);
        $parser = new DataParser();
        if ($parser->strToTime($data['bookableFrom'] ?? null) !== false) {
            $object->setBookableFrom(date_create($data['bookableFrom']));
        }
        if ($parser->strToTime($data['bookableTill'] ?? null) !== false) {
            $object->setBookableTill(date_create($data['bookableTill']));
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setPvPrice(round((float)($data['price'] ?? null), 2));
        $object->setPvTax(round((float)($data['tax'] ?? null), 2));
        $object->setPvTaxRate(round((float)($data['taxRate'] ?? null), 2));
        $object->setPvQuota((int)($data['quota'] ?? null));
        if (!empty($data['priceCategory'])) {
            $refArray = ['@id' => $data['priceCategory'], '@type' => 'PriceCategory'];
            $refImportModel = $this->getImportMappingModelByReference($refArray)?->getDomainModel();
            if ($refImportModel instanceof PriceCategory) {
                $object->setPriceCategory($refImportModel);
            } else {
                $object->setPriceCategory(null);
            }
        }
        // directCheckoutUrl is dynamically generated and changes every time. Old urls are still value
        if (!$object->getUid() || !$object->getDirectCheckoutUrl() || $object->objectIsDirtyDeep() ){
            $urls = [];
            if (array_key_exists('@urls', $data)) {
                $urls = $data['@urls'];
            }
            if (empty($urls['directCheckoutUrl']) && !empty($data['directCheckoutUrl'])) {
                $urls['directCheckoutUrl'] = $data['directCheckoutUrl'];
            }
            $object->setDirectCheckoutUrl(trim((string)($urls['directCheckoutUrl'] ?? '')));
        }
    }

    protected function assignPriceCategoryProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof PriceCategory) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
    }

    protected function assignTimeRangeProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof TimeRange) || empty($data)) {
            return;
        }
        $parser = new DataParser();
        if ($tstamp = $parser->strToTime($data['endDate'] ?? null)) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $parser->strToTime($data['startDate'] ?? null)) {
            $object->setStartTstamp($tstamp);
        }
        $object->setEndDateIsSet(!empty($data['endDateTimeIsSet']));
        $object->setStartDateIsSet(!empty($data['startDateTimeIsSet']));
        $this->assignMandatoryEntityReference($importMappingModel, 'eventSession');
    }

    protected function assignSponsorProperties(ImportMappingModel $importMappingModel): void
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Sponsor) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setUrl($this->cropFieldValue($data, 'url', 255));
        $object->setImageHash($data['imageHash'] ?? '');
        if (!empty($data['imageFile']['url'])) {
            $this->fileImporter->enqueueFileMapping($object, 'image_file', $data['imageFile']);
        } elseif ($object->getImageFile() && $object->getImageFile()->count() > 0) {
            foreach ($object->getImageFile() as $fileReference) {
                $this->fileImporter->deleteObsoleteFileReferenceOnImport($fileReference);
            }
            $object->setImageFile(new ObjectStorage());
        }
    }

    /**
     * Assigns a mandator entity reference to the domain model of the given import mapping model.
     *
     * @param ImportMappingModel $importMappingModel The mapping model containing the domain model and import data.
     * @param string $dataKey The key used to locate the reference data in the import data, defaults to 'event'.
     * @return void
     * @throws InvalidArgumentException If the referenced entity does not exist for the import entry.
     */
    private function assignMandatoryEntityReference(ImportMappingModel $importMappingModel, string $dataKey = 'event'): void
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof BelongsToEventInterface) || empty($data)) {
            return;
        }
        if (!empty($data[$dataKey])) {
            $refArray = $data[$dataKey];
            if (!is_array($refArray)) {
                $refType = $dataKey === 'eventSession' ? 'EventSession' : 'Event';
                $refArray = ['@id' => $refArray, '@type' => $refType];
            }
            $refId = $refArray['@id'];
            $referencedObject = $this->getImportMappingModelByReference($refArray)?->getDomainModel();
            if ($dataKey === 'event' && $referencedObject instanceof Event) {
                $object->setEvent($referencedObject);
                return;
            }
            if ($dataKey === 'eventSession' && $referencedObject instanceof EventSession
                && method_exists($object, 'setEventSession')) {
                $object->setEventSession($referencedObject);
                return;
            }
            $message = sprintf('The reference %s does not exist for import entry %s:%d',
                $refId,
                $importMappingModel->getImportType(),
                $importMappingModel->getImportId()
            );
            throw new InvalidArgumentException($message);
        }
    }
}
