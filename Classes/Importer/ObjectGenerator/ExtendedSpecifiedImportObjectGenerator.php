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
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Location;
use BrainAppeal\CampusEventsConnector\Domain\Model\Organizer;
use BrainAppeal\CampusEventsConnector\Domain\Model\PriceCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\Referent;
use BrainAppeal\CampusEventsConnector\Domain\Model\Sponsor;
use BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup;
use BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange;
use BrainAppeal\CampusEventsConnector\Domain\Model\ViewList;
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
        $object->setDirectRegistrationUrl($urls['directRegistrationUrl'] ?? '');
        if ($tstamp = $this->strToTime($data['modifiedAtRecursive'])) {
            $object->setModifiedAtRecursive($tstamp);
        }
        if ($tstamp = $this->strToTime($data['startDate'])) {
            $object->setStartTstamp($tstamp);
        }
        if ($tstamp = $this->strToTime($data['endDate'])) {
            $object->setEndTstamp($tstamp);
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
        $object->setCanceled(!empty($data['canceled']));

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
        if (!empty($data['viewLists'])){
            $this->processReferencesMultiple(
                $object,
                $data['viewLists'],
                'viewList'
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
        $object->setStreetName($this->cropFieldValue($data, 'street_name', 255));
        $object->setTown($this->cropFieldValue($data, 'town', 255));
        $object->setZipCode($this->cropFieldValue($data, 'zip_code', 255));
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
        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength);
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

    protected function assignEventAttachmentProperties(ImportMappingModel $importMappingModel)
    {
        $object = $importMappingModel->getDomainModel();
        $data = $importMappingModel->getImportData();
        if (!($object instanceof EventAttachment) || empty($data)) {
            return;
        }
        $object->setName($this->cropFieldValue($data, 'name', 255));
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
        $object->setName($this->cropFieldValue($data, 'name', 255));
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
        if ($tstamp = $this->strToTime($data['endDate'])) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $this->strToTime($data['startDate'])) {
            $object->setStartTstamp($tstamp);
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
        if ($this->strToTime($data['bookableFrom']) !== false) {
            $object->setBookableFrom(date_create($data['bookableFrom']));
        }
        if ($this->strToTime($data['bookableTill']) !== false) {
            $object->setBookableTill(date_create($data['bookableTill']));
        }
        $urls = [];
        if (array_key_exists('@urls', $data)) {
            $urls = $data['@urls'];
        }
        if (empty($urls['directCheckoutUrl']) && !empty($data['directCheckoutUrl'])) {
            $urls['directCheckoutUrl'] = $data['directCheckoutUrl'];
        }
        $object->setDirectCheckoutUrl($urls['directCheckoutUrl'] ?? '');
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setPrice($this->cropFieldValue($data, 'price', 255));
        $object->setQuota($this->cropFieldValue($data, 'quota', 255));
        $object->setTax($this->cropFieldValue($data, 'tax', 255));
        $object->setTaxRate($this->cropFieldValue($data, 'taxRate', 255));
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
        if ($tstamp = $this->strToTime($data['endDate'])) {
            $object->setEndTstamp($tstamp);
        }
        if ($tstamp = $this->strToTime($data['startDate'])) {
            $object->setStartTstamp($tstamp);
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
        $object->setName($this->cropFieldValue($data, 'name', 255));
        $object->setUrl($this->cropFieldValue($data, 'url', 255));
        $object->setImageHash($data['imageHash']);
        if ($data['imageFile']['url']) {
            $this->fileImporter->enqueueFileMapping($object, 'image_file', $data['imageFile']);
        }
    }

    /**
     * Returns a valid unix timestamp or false
     *
     * @param string|mixed|null $dateValue
     * @return false|int
     */
    protected function strToTime($dateValue)
    {
        if (!empty($dateValue) && ($tstamp = strtotime($dateValue)) && $tstamp <= self::UNIX_TIMESTAMP_MAX) {
            return $tstamp;
        }
        return false;
    }
}
