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
use BrainAppeal\CampusEventsConnector\Importer\ImportMappingModel;

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
        $object->setName($data['name']);
        $urls = [];
        if (array_key_exists('@urls', $data)) {
            $urls = $data['@urls'];
        }
        if (empty($urls['eventUrl']) && !empty($this->baseUri)) {
            $urls['eventUrl'] = rtrim($this->baseUri, '/') . '/event/' . $importMappingModel->getImportId();
        }
        $object->setUrl($urls['eventUrl'] ?? '');
        if ($data['subtitle']) {
            $object->setSubtitle($data['subtitle']);
        }
        if ($data['description']) {
            $object->setDescription($this->cleanupHtmlForRTE($data['description']));
        }
        if ($data['shortDescription']) {
            $object->setShortDescription($data['shortDescription']);
        }
        if ($data['disturberMessage']) {
            $object->setDisturberMessage($data['disturberMessage']);
        }
        if ($data['eventAttendanceMode']) {
            $object->setEventAttendanceMode($data['eventAttendanceMode']);
        }
        if ($data['eventNumber']) {
            $object->setEventNumber($data['eventNumber']);
        }
        if ($data['externalOrderEmailAddress']) {
            $object->setExternalOrderEmailAddress($data['externalOrderEmailAddress']);
        }
        if ($data['externalOrderUrl']) {
            $object->setExternalOrderUrl($data['externalOrderUrl']);
        }
        if (strtotime($data['modifiedAtRecursive'])) {
            $modifiedAtRecursive = strtotime($data['modifiedAtRecursive']);
            $object->setModifiedAtRecursive($modifiedAtRecursive);
        }
        if (strtotime($data['startDate'])) {
            $object->setStartTstamp(strtotime($data['startDate']));
        }
        if (strtotime($data['endDate'])) {
            $object->setEndTstamp(strtotime($data['endDate']));
        }
        if ($data['orderType']) {
            $object->setOrderType($data['orderType']);
        }
        if ($data['referentsTitle']) {
            $object->setReferentsTitle($data['referentsTitle']);
        }
        if ($data['seoTitle']) {
            $object->setSeoTitle($data['seoTitle']);
        }
        if ($data['seoDescription']) {
            $object->setSeoDescription($data['seoDescription']);
        }
        if ($data['sponsorsTitle']) {
            $object->setSponsorsTitle($data['sponsorsTitle']);
        }

        if (!empty($data['organizers'])){
            $this->processReferencesMultiple(
                $object,
                $data['organizers'],
                'organizer',
                'organizer'
            );
        }
        if (!empty($data['targetGroups'])){
            $this->processReferencesMultiple(
                $object,
                $data['targetGroups'],
                'targetGroup'
            );
        }
        if (!empty($data['filterCategories'])){
            $this->processReferencesMultiple(
                $object,
                $data['filterCategories'],
                'filterCategory',
                'filterCategories'
            );
        }
        if (!empty($data['categories'])){
            $this->processReferencesMultiple(
                $object,
                $data['categories'],
                'category',
                'categories'
            );
        }
        if (!empty($data['alternativeEvents'])){
            $this->processReferencesMultiple(
                $object,
                $data['alternativeEvents'],
                'alternativeEvent'
            );
        }
        if (!empty($data['contactPersons'])){
            $this->processReferencesMultiple(
                $object,
                $data['contactPersons'],
                'contactPerson'
            );
        }
        if (!empty($data['attachments'])){
            $this->processReferencesMultiple(
                $object,
                $data['attachments'],
                'eventAttachment'
            );
        }
        if (!empty($data['images'])){
            $this->processReferencesMultiple(
                $object,
                $data['images'],
                'eventImage'
            );
        }
        if (!empty($data['eventSessions'])){
            $this->processReferencesMultiple(
                $object,
                $data['eventSessions'],
                'eventSession'
            );
        }
        if (!empty($data['eventTicketPriceVariants'])){
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
        if (!empty($locations)){
            $this->processReferencesMultiple(
                $object,
                $locations,
                'location'
            );
            if ((null !== $objectLocations = $object->getLocations()) && $objectLocations->count() > 0) {
                foreach ($objectLocations as $location) {
                    if ($location instanceof Location) {
                        $object->setLocation($location);
                        break;
                    }
                }
            }

        }
        if (!empty($data['referents'])){
            $this->processReferencesMultiple(
                $object,
                $data['referents'],
                'referent'
            );
        }
        if (!empty($data['sponsors'])){
            $this->processReferencesMultiple(
                $object,
                $data['sponsors'],
                'sponsor'
            );
        }
    }

    /**
     * Remove line breaks after end tags in html, to prevent RTE from adding unnecessary empty lines
     * @param string $html
     * @return string
     */
    private function cleanupHtmlForRTE($html)
    {
        $replaceHtmlWith = [
            '<br>' => ['<br />', '<br/>'],
            'ä' => '&auml;',
            'ö' => '&ouml;',
            'ü' => '&uuml;',
            'Ä' => '&Auml;',
            'Ö' => '&Ouml;',
            'Ü' => '&Uuml;',
            'ß' => '&szlig;',
        ];
        $cleanedHtml = $html;
        foreach ($replaceHtmlWith as $replaceWith => $searchFor) {
            $cleanedHtml = str_replace($searchFor, $replaceWith, $cleanedHtml);
        }
        $removeLineBreaksBeforeAndAfterTags = ['br', 'p', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'div', 'table'];
        try {
            foreach ($removeLineBreaksBeforeAndAfterTags as $tag) {
                $tagStart = '<' . $tag . '>';
                $tagEnd = '</' . $tag . '>';
                if (stripos($cleanedHtml, $tagStart) !== false) {
                    $cleanedHtml = preg_replace('/\s*(<' . $tag . '[^>]*>)\s*/i', '$1', $cleanedHtml);
                }
                if (stripos($cleanedHtml, $tagEnd) !== false) {
                    $cleanedHtml = preg_replace('/\s*(<\/' . $tag . '>)\s*/i', '$1', $cleanedHtml);
                }
            }
        } catch (\Exception $e) {
            return $html;
        }
        return $cleanedHtml;
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
            $parentImportMappingModel = $this->getImportMappingModelByReference($data['parent']);
            $parentModel = $parentImportMappingModel->getDomainModel();
            if ($parentModel instanceof FilterCategory) {
                $object->setParent($parentModel);
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
        $object->setStreetName($data['street_name'] ?? '');
        $object->setTown($data['town'] ?? '');
        $object->setZipCode($data['zip_code'] ?? '');
        $object->setBuilding($data['building'] ?? '');
        $object->setLatitude($data['latitude'] ?? '');
        $object->setListViewDisplayName($data['listViewDisplayName'] ?? '');
        $object->setLongitude($data['longitude'] ?? '');
        $object->setRoom($data['room'] ?? '');
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
        $object->setName($data['name'] ?? '');
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
        $object->setAcademicDegree($data['academicDegree'] ?? '');
        $object->setBusinessAddress($data['businessAddress'] ?? '');
        $object->setDescription($data['description'] ?? '');
        $object->setEmail($data['email'] ?? '');
        $object->setEventFormats($data['eventFormats'] ?? '');
        $object->setExternalUrl($data['externalUrl'] ?? '');
        $object->setFirstName($data['firstName'] ?? '');
        $object->setFocusOfWork($data['focusOfWork'] ?? '');
        $object->setInstitution($data['institution'] ?? '');
        $object->setLastName($data['lastName'] ?? '');
        $object->setPhone($data['phone'] ?? '');
        $object->setPublications($data['publications'] ?? '');
        $object->setReferences($data['references'] ?? '');
        $object->setTitle($data['title'] ?? '');
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
        $object->setName($data['name'] ?? '');
    }


    protected function assignContactPersonProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof ContactPerson) || empty($data)) {
            return;
        }
        $object->setDepartment($data['department'] ?? '');
        $object->setFirstName($data['firstName'] ?? '');
        $object->setInstitution($data['institution'] ?? '');
        $object->setLastName($data['lastName'] ?? '');
        $object->setMailAddress($data['mailAddress'] ?? '');
        $object->setPhone($data['phone'] ?? '');
        $object->setPosition($data['position'] ?? '');
        $object->setTitle($data['title'] ?? '');
    }

    protected function assignEventAttachmentProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventAttachment) || empty($data)) {
            return;
        }
        $object->setName($data['name'] ?? '');
        $object->setFileHash($data['fileHash'] ?? '');
        if ($data['attachmentFile']['url']) {
            $this->fileImporter->enqueueFileMapping($object, 'attachment_file', $data['attachmentFile']);
        }
    }

    protected function assignEventImageProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventImage) || empty($data)) {
            return;
        }
        $object->setName($data['name'] ?? '');
        $object->setFileHash($data['fileHash'] ?? '');
        if ($data['imageFile']['url']) {
            $this->fileImporter->enqueueFileMapping($object, 'image_file', $data['imageFile']);
        }
    }

    protected function assignEventSessionProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventSession) || empty($data)) {
            return;
        }
        if (strtotime($data['endDate'])) {
            $object->setEndTstamp(strtotime($data['endDate']));
        }
        if (strtotime($data['startDate'])) {
            $object->setStartTstamp(strtotime($data['startDate']));
        }
        if (!empty($data['sessionDates'])){
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
        if (strtotime($data['bookableFrom'])) {
            $object->setBookableFrom(date_create($data['bookableFrom']));
        }
        if (strtotime($data['bookableTill'])) {
            $object->setBookableTill(date_create($data['bookableTill']));
        }
        $object->setDirectCheckoutUrl($data['directCheckoutUrl'] ?? '');
        $object->setName($data['name'] ?? '');
        $object->setPrice($data['price'] ?? '');
        $object->setQuota($data['quota'] ?? '');
        $object->setTax($data['tax'] ?? '');
        $object->setTaxRate($data['taxRate'] ?? '');
    }

    protected function assignPriceCategoryProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof PriceCategory) || empty($data)) {
            return;
        }
        $object->setName($data['name'] ?? '');
    }

    protected function assignTimeRangeProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof TimeRange) || empty($data)) {
            return;
        }
        if (strtotime($data['endDate'])) {
            $object->setEndTstamp(strtotime($data['endDate']));
        }
        if (strtotime($data['startDate'])) {
            $object->setStartTstamp(strtotime($data['startDate']));
        }
        $object->setEndDateIsSet($data['endDateTimeIsSet']);
        $object->setStartDateIsSet($data['startDateTimeIsSet']);
        if (null === $object->getEvent() && (null !== $eventSession = $object->getEventSession())
            && null !== $event = $eventSession->getEvent()){
            $object->setEvent($event);
        }
    }

    protected function assignSponsorProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof Sponsor) || empty($data)) {
            return;
        }
        $object->setName($data['name']);
        $object->setUrl($data['url'] ?? '');
        $object->setImageHash($data['imageHash']);
        if ($data['imageFile']['url']) {
            $this->fileImporter->enqueueFileMapping($object, 'image_file', $data['imageFile']);
        }
    }
}
