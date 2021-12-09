<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

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
use BrainAppeal\CampusEventsConnector\Http\Client;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ExtendedApiConnector
{
    const BASE_PATH = '/api/';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var string
     */
    private $apiVersion = '2.7';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * @var array
     */
    protected $apiTypeMapping = [];

    /**
     * Entry points for api data types
     *
     * @var string[]|array<string, string>
     */
    protected static $apiTypeEntryPoints = [
        'Event' => 'events',
        'ContactPerson' => 'contact_persons',
        'Category' => 'categories',
        'EventSession' => 'event_sessions',
        'EventAttachment' => 'event_attachments',
        'EventImage' => 'event_images',
        'EventTicketPriceVariant' => 'event_ticket_price_variants',
        'FilterCategory' => 'filter_categories',
        'Location' => 'locations',
        'Organizer' => 'organizers',
        'PriceCategory' => 'price_categories',
        'Referent' => 'referents',
        'SessionTimePeriod' => 'session_time_periods',
        'Sponsor' => 'sponsors',
        'TargetGroup' => 'target_groups',
    ];

    /**
     * @param string $data
     * @param array $additionalParams
     * @return string
     */
    private function generateUri($data, $additionalParams)
    {
        return self::BASE_PATH . $data;
    }

    /** @noinspection PhpDocRedundantThrowsInspection */
    /**
     * @param string $data
     * @param array $additionalParams
     * @return array
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    public function getApiResponse($data, $additionalParams = [])
    {
        $uri = $this->generateUri($data, $additionalParams);
        /** @var \GuzzleHttp\Client $client */
        $client = new Client(
            [
                'base_uri' => $this->baseUri,
                'defaults' => [
                    'headers' => ['Authorization' => 'Bearer '.$this->apiKey],
                ]
            ]);
        try {
            $response = $client->get($uri);
            $response = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $response = [];
        }


        return $response;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return self
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     * @return self
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     * @return self
     */
    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    /**
     * @return bool
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    public function checkApiVersion()
    {
        $response = $this->getApiResponse('api-check');
        return !empty($response['status']['version']) && $response['status']['version'] == $this->getApiVersion();
    }

    /**
     * @param string $itemType The api model data type, e.g. Event, ContactPerson, etc.
     * @return array
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    public function fetchItemListForType($itemType)
    {
        $typeMapping = $this->getMappingForType($itemType);
        if (empty($typeMapping)) {
            return [];
        }
        $path = $typeMapping['uri'];
        $apiResponse = $this->getApiResponse($path);
        $allListItems = [];
        if (!empty($apiResponse['hydra:member'])) {
            foreach ($apiResponse['hydra:member'] as $listItem) {
                $allListItems[] = $listItem;
            }
            if (!empty($apiResponse['hydra:view']['hydra:next'])) {
                $importMore = true;
                while ($importMore) {
                    $pageParam = substr($apiResponse['hydra:view']['hydra:next'], strrpos($apiResponse['hydra:view']['hydra:next'], '?page='));
                    $apiResponse = $this->getApiResponse($path.$pageParam);
                    foreach ($apiResponse['hydra:member'] as $listItem) {
                        $allListItems[] = $listItem;
                    }
                    if (!$apiResponse['hydra:view']['hydra:next']) {
                        $importMore = false;
                    }
                }
            }
        }
        return $allListItems;
    }

    /**
     * @return string[]
     */
    public function getApiImportTypes(): array
    {
        return array_keys(self::$apiTypeEntryPoints);
    }

    /**
     * Returns the import data mapping for all registered types
     *
     * @return string[]
     */
    public function getDataMap(): array
    {
        $dataMap = [];
        foreach (array_keys(self::$apiTypeEntryPoints) as $importType) {
            $dataMap[$importType] = $this->getMappingForType($importType);
        }
        return $dataMap;
    }

    /**
     * Returns the id for the given reference string
     *
     * @param string $apiReferenceId
     * @param string $importType
     * @return int
     */
    public static function filterId($apiReferenceId, $importType)
    {
        if (array_key_exists($importType, self::$apiTypeEntryPoints)) {
            $path = self::BASE_PATH . self::$apiTypeEntryPoints[$importType] . '/';
            preg_match('#'.preg_quote($path, '#').'(\d+)#i', $apiReferenceId, $matches);
            if (!empty($matches[1])) {
                return (int) $matches[1];
            }
        }
        return (int) filter_var($apiReferenceId, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Returns the mapping information for the given data type
     * @param string $importType
     */
    public function getMappingForType($importType)
    {
        if (array_key_exists($importType, $this->apiTypeMapping)) {
            return $this->apiTypeMapping[$importType];
        }
        if (!array_key_exists($importType, self::$apiTypeEntryPoints)) {
            return null;
        }
        $importTypeClassMap = [
            'Event' => Event::class,
            'ContactPerson' => ContactPerson::class,
            'Category' => Category::class,
            'EventSession' => EventSession::class,
            'EventAttachment' => EventAttachment::class,
            'EventImage' => EventImage::class,
            'EventTicketPriceVariant' => EventTicketPriceVariant::class,
            'FilterCategory' => FilterCategory::class,
            'Location' => Location::class,
            'Organizer' => Organizer::class,
            'PriceCategory' => PriceCategory::class,
            'Referent' => Referent::class,
            'SessionTimePeriod' => TimeRange::class,
            'Sponsor' => Sponsor::class,
            'TargetGroup' => TargetGroup::class,
        ];
        $class = $importTypeClassMap[$importType];
        $dataMapper = $this->getDataMapper();
        $tableName = $dataMapper->getDataMap($class)->getTableName();
        $this->apiTypeMapping[$importType] = [
            'class' => $class,
            'uri' => self::$apiTypeEntryPoints[$importType],
            'table' => $tableName,
        ];
        return $this->apiTypeMapping[$importType];
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected function getDataMapper()
    {
        if (null === $this->dataMapper) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
            $this->dataMapper = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        }
        return $this->dataMapper;
    }

    /**
     * @param int $importId
     * @param string $importType
     * @return array|null The API result data
     */
    public function fetchRecordData($importId, $importType)
    {
        $typeMapping = $this->getMappingForType($importType);
        if (null !== $typeMapping) {
            $uri = $typeMapping['uri'] . '/'.$importId;
            return $this->getApiResponse($uri);
        }

        return null;
    }

}
