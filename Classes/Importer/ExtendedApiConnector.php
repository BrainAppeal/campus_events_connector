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
use BrainAppeal\CampusEventsConnector\Domain\Model\ViewList;
use BrainAppeal\CampusEventsConnector\Http\Client;
use BrainAppeal\CampusEventsConnector\Http\HttpException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class ExtendedApiConnector
{
    public const BASE_PATH = '/api/';

    /**
     * @var ?string
     */
    private ?string $apiKey = null;

    /**
     * @var ?string
     */
    private ?string $baseUri = null;

    /**
     * @var string
     */
    private $apiVersion = '2.7';

    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var array
     */
    protected array $apiTypeMapping = [];

    /**
     * @var array
     */
    protected array $exceptions = [];

    /**
     * @var int
     */
    protected int $successfulResponseCount = 0;

    /**
     * @var ?array
     */
    private ?array $acceptLanguages = null;

    /**
     * The number of days for which the local cache is considered valid in a development environment.
     */
    private int $developmentLocalCacheAgeInDays = 0;

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
        'ViewList' => 'view_lists',
    ];

    public const IMPORT_TYPE_CLASS_MAP = [
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
        'ViewList' => ViewList::class,
    ];

    public const IMPORT_TYPE_TABLE_MAP = [
        'ContactPerson' => 'tx_campuseventsconnector_domain_model_contactperson',
        'TargetGroup' => 'tx_campuseventsconnector_domain_model_targetgroup',
        'Category' => 'tx_campuseventsconnector_domain_model_category',
        'Location' => 'tx_campuseventsconnector_domain_model_location',
        'Organizer' => 'tx_campuseventsconnector_domain_model_organizer',
        'PriceCategory' => 'tx_campuseventsconnector_domain_model_pricecategory',
        'Referent' => 'tx_campuseventsconnector_domain_model_referent',
        'Sponsor' => 'tx_campuseventsconnector_domain_model_sponsor',
        'ViewList' => 'tx_campuseventsconnector_domain_model_viewlist',
        'FilterCategory' => 'tx_campuseventsconnector_domain_model_filtercategory',
        'EventSession' => 'tx_campuseventsconnector_domain_model_eventsession',
        'EventAttachment' => 'tx_campuseventsconnector_domain_model_eventattachment',
        'EventImage' => 'tx_campuseventsconnector_domain_model_eventimage',
        'EventTicketPriceVariant' => 'tx_campuseventsconnector_domain_model_eventticketpricevariant',
        'SessionTimePeriod' => 'tx_campuseventsconnector_domain_model_timerange',
        'Event' => 'tx_campuseventsconnector_domain_model_event',
    ];

    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param string $relativeUrl
     * @param array<string, string> $additionalParams Additional url parameters
     * @return string
     */
    private function generateUri(string $relativeUrl, array $additionalParams): string
    {
        $url = self::BASE_PATH . $relativeUrl;
        if (!empty($additionalParams)) {
            $paramStr = !str_contains($url, '?') ? '?' : '&';
            foreach ($additionalParams as $key => $value) {
                $url .= $paramStr . $key . '=' . urlencode((string)$value);
                $paramStr = '&';
            }
        }
        return $url;
    }

    /**
     * @param string $relativeUrl
     * @param array<string, string> $additionalParams Additional url parameters
     * @return array
     */
    public function getApiResponse(string $relativeUrl, array $additionalParams = []): array
    {
        $localCacheFilePath = $this->getLocalCachePathForDevelopment($relativeUrl, $additionalParams);
        if ($localResponse = $this->getLocalResponseCachedFile($localCacheFilePath)) {
            return $localResponse;
        }
        $response = $this->getApiResponseForLanguage($relativeUrl, $additionalParams);
        if (!empty($this->acceptLanguages)) {
            foreach ($this->acceptLanguages as $languageCode) {
                // The default language of campus events is German
                if ($languageCode !== 'de' && $languageCode !== 'default') {
                    $translationResponse = $this->getApiResponseForLanguage($relativeUrl, $additionalParams, $languageCode);
                    $response['translations'][$languageCode] = $translationResponse;
                }
            }
        }
        // Write to local dev cache only if content is not empty and in the development context
        if (!empty($response) && $localCacheFilePath) {
            file_put_contents($localCacheFilePath, json_encode($response));
        }
        return $response;
    }

    /**
     * @param string $relativeUrl
     * @param array<string, string> $additionalParams Additional url parameters
     * @return array
     */
    protected function getApiResponseForLanguage(string $relativeUrl, array $additionalParams = [], ?string $languageCode = null): array
    {
        $uri = $this->generateUri($relativeUrl, $additionalParams);
        $headers = [
            'X-API-KEY' => $this->apiKey,
        ];
        if ($languageCode !== null) {
            $headers['Accept-Language'] = $languageCode;
        }
        $client = new Client(
            [
                'base_uri' => rtrim($this->baseUri, '/'),
                'headers' => $headers,
            ]
        );
        try {
            $response = $client->get($uri);
            $response = json_decode($response->getBody(), true);
            ++$this->successfulResponseCount;
        } catch (HttpException $e) {
            // Don't add an exception for Bad request or Not found errors
            if (!in_array($e->getCode(), [400, 404])) {
                $this->exceptions[] = $e;
            }
            $logger = self::getLogger();
            $logger->error($e->getMessage(), [
                'apiUrl' => $uri,
            ]);
            $response = [];
        } catch (\Exception $e) {
            $this->exceptions[] = $e;
            $logger = self::getLogger();
            $logger->error($e->getMessage(), [
                'apiUrl' => $uri,
            ]);
            $response = [];
        }

        return $response;
    }

    /**
     * @return ?string
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return static
     */
    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     * @return static
     */
    public function setBaseUri(string $baseUri): static
    {
        if (!str_contains($baseUri, 'http')) {
            $baseUri = 'https://' . $baseUri;
        }
        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     * @return static
     */
    public function setApiVersion(string $apiVersion): static
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    public function getAcceptLanguages(): ?array
    {
        return $this->acceptLanguages;
    }

    public function setAcceptLanguages(?array $acceptLanguages): void
    {
        $this->acceptLanguages = $acceptLanguages;
    }

    /**
     * @return bool
     * @throws \BrainAppeal\CampusEventsConnector\Http\HttpException
     */
    public function checkApiVersion(): bool
    {
        $response = $this->getApiResponse('events');
        return !empty($response) && !empty($response['@id']);
    }

    /**
     * Checks if the list item contains all required data based on the given import model type.
     *
     * @param string $importModelType The import model type to evaluate.
     * @return bool True if the list item contains all required data, false otherwise.
     */
    public function listItemContainsAllData(string $importModelType): bool
    {
        return in_array(
            $importModelType,
            [
                'EventAttachment', 'EventImage', 'EventSession',
                'FilterCategory', 'Category', 'Organizer', 'PriceCategory',
                'TargetGroup', 'ViewList', 'Sponsor', 'SessionTimePeriod',
            ]
        );
    }

    /**
     * Fetches a list of items for the specified type by making API calls and processing paginated responses.
     *
     * @param string $itemType The type of items to fetch, used to determine the appropriate API endpoint.
     * @return array A list of items retrieved from the API, or an empty array if no items are found or the type mapping is invalid.
     */
    public function fetchItemListForType(string $itemType): array
    {
        $typeMapping = $this->getMappingForType($itemType);
        if (empty($typeMapping)) {
            return [];
        }
        $path = $typeMapping['uri'];
        $apiResponse = $this->getApiResponse($path);
        $allListItems = [];
        if (!empty($apiResponse['hydra:member'])) {
            $this->addListItemsFromResponse($allListItems, $apiResponse, $itemType);
            if (!empty($apiResponse['hydra:view']['hydra:next'])) {
                $importMore = true;
                $maxPageCount = 9999;
                $page = 1;
                $nextPage = null;
                $pageParamPrefix = '?page=';
                while ($importMore && $page < $maxPageCount) {
                    $importMore = false;
                    $nextPageUri = $apiResponse['hydra:view']['hydra:next'];
                    preg_match('/' . preg_quote($pageParamPrefix, '/') . '(\d+)/', (string)$nextPageUri, $pageMatches);
                    if (!empty($pageMatches[1])) {
                        $nextPage = (int)$pageMatches[1];
                    }
                    if ($nextPage > $page) {
                        $apiResponse = $this->getApiResponse($path . $pageParamPrefix . $nextPage);
                        $this->addListItemsFromResponse($allListItems, $apiResponse, $itemType);
                        $page = $nextPage;
                        if (!empty($apiResponse['hydra:view']['hydra:next'])) {
                            $importMore = true;
                        }
                    }
                }
            }
        }
        return $allListItems;
    }

    /**
     * Adds list items from the API response to the provided list, organizing translations and associating them with their respective items.
     *
     * @param array $allListItems Reference to the list where processed items will be added.
     * @param array $apiResponse The API response containing the list items and their associated translations.
     * @param string $itemType The type of items being processed to assist with ID filtering.
     */
    protected function addListItemsFromResponse(array &$allListItems, array $apiResponse, string $itemType): void
    {
        if (!empty($apiResponse['hydra:member'])) {
            $allTranslations = [];
            foreach (($apiResponse['translations'] ?? []) as $languageCode => $translationResponse) {
                foreach ($translationResponse['hydra:member'] as $listItem) {
                    $importId = self::filterId($listItem['@id'], $itemType);
                    $allTranslations[$importId][$languageCode] = $listItem;
                }
            }
            foreach ($apiResponse['hydra:member'] as $listItem) {
                $importId = self::filterId($listItem['@id'], $itemType);
                if (isset($allTranslations[$importId])) {
                    $listItem['translations'] = $allTranslations[$importId];
                }
                $allListItems[$importId] = $listItem;
            }
        }
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
     * @return array<string, array{class: string, uri: string, table: string}>
     */
    public function getDataMap(): array
    {
        $dataMap = [];
        foreach (array_keys(self::$apiTypeEntryPoints) as $importType) {
            if ($typeMapping = $this->getMappingForType($importType)) {
                $dataMap[$importType] = $typeMapping;
            }
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
    public static function filterId(string $apiReferenceId, string $importType): int
    {
        if (array_key_exists($importType, self::$apiTypeEntryPoints)) {
            $path = self::BASE_PATH . self::$apiTypeEntryPoints[$importType] . '/';
            preg_match('#' . preg_quote($path, '#') . '(\d+)#i', $apiReferenceId, $matches);
            if (!empty($matches[1])) {
                return (int)$matches[1];
            }
        }
        return (int)filter_var($apiReferenceId, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Returns the mapping information for the given data type
     * @param string $importType
     * @return ?array{class: string, uri: string, table: string}
     * @throws Exception
     */
    public function getMappingForType(string $importType): ?array
    {
        if (array_key_exists($importType, $this->apiTypeMapping)) {
            return $this->apiTypeMapping[$importType];
        }
        if (!array_key_exists($importType, self::$apiTypeEntryPoints)) {
            return null;
        }
        $importTypeClassMap = self::IMPORT_TYPE_CLASS_MAP;
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
     * @return DataMapper
     */
    protected function getDataMapper(): DataMapper
    {
        if ($this->dataMapper === null) {
            $this->dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        }
        return $this->dataMapper;
    }

    /**
     * @param int $importId
     * @param string $importType
     * @return array|null The API result data
     */
    public function fetchRecordData(int $importId, string $importType): ?array
    {
        $typeMapping = $this->getMappingForType($importType);
        if ($typeMapping !== null) {
            $uri = $typeMapping['uri'] . '/' . $importId;
            return $this->getApiResponse($uri);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function getSuccessfulResponseCount(): int
    {
        return $this->successfulResponseCount;
    }

    /**
     * @return LoggerInterface
     */
    protected static function getLogger(): LoggerInterface
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
    }

    public function setDevelopmentLocalCacheAgeInDays(int $developmentLocalCacheAgeInDays): void
    {
        $this->developmentLocalCacheAgeInDays = $developmentLocalCacheAgeInDays;
    }

    /**
     * Retrieves the cached API response from a local file if it exists and is still valid.
     *
     * @param string|null $localCacheFilePath The path to the local cache file, or null if no cache file is used.
     * @return array|null The decoded response content as an associative array if the cached file is valid and readable, or null otherwise.
     */
    private function getLocalResponseCachedFile(?string $localCacheFilePath): ?array
    {
        if (!$localCacheFilePath || $this->developmentLocalCacheAgeInDays === 0 || !file_exists($localCacheFilePath)) {
            return null;
        }
        if (filemtime($localCacheFilePath) < (time() - 60 * 60 * 24 * $this->developmentLocalCacheAgeInDays)) {
            unlink($localCacheFilePath);
            return null;
        }
        $responseContent = file_get_contents($localCacheFilePath);
        if ($responseContent !== false) {
            $localResponse = json_decode($responseContent, true);
            if (empty($localResponse) || array_key_exists('error', $localResponse)) {
                unlink($localCacheFilePath);
                return null;
            }
            return $localResponse;
        }
        return null;
    }

    /**
     * Generates the local cache file path for development context based on the given source type, source ID, and optional parameters.
     *
     * @param string $relativeUrl
     * @param array<string, string> $additionalParams Additional url parameters
     * @return string|null The generated local cache file path or null if local caching is not enabled or outside the development context.
     */
    private function getLocalCachePathForDevelopment(string $relativeUrl, array $additionalParams = []): ?string
    {
        if ($this->developmentLocalCacheAgeInDays > 0 && Environment::getContext()->isDevelopment()) {
            // Build a cache key from URL and params
            $cacheKey = $relativeUrl;
            if (!empty($additionalParams)) {
                foreach ($additionalParams as $key => $value) {
                    $cacheKey .= '-' . $key . '_' . $value;
                }
            }

            // Sanitize the cache key so it can be used safely as a file name
            // 1) Remove directory separators and query fragments by replacing any non safe char
            $safeKey = (string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $cacheKey);
            // 2) Avoid leading dot (hidden files) and trim redundant underscores/dots/hyphens
            $safeKey = ltrim($safeKey, '.');
            $safeKey = trim($safeKey, '_-.');
            // 3) Ensure we still have something usable
            if ($safeKey === '') {
                $safeKey = 'cache';
            }
            // 4) Limit length and keep uniqueness using a hash suffix
            $hash = substr(sha1($cacheKey), 0, 12);
            // Max filename length on most filesystems is 255 bytes; leave room for suffix and extension
            $maxBaseLength = 255 - 1 /*dot*/ - 4 /*json*/ - 1 /*dash*/ - strlen($hash);
            if (strlen($safeKey) > $maxBaseLength) {
                $safeKey = substr($safeKey, 0, $maxBaseLength);
            }
            $fileName = $safeKey . '-' . $hash . '.json';

            $localCachePath = Environment::getVarPath() . '/transient/ce-api/';
            if (!is_dir($localCachePath)) {
                GeneralUtility::mkdir_deep($localCachePath);
            }
            return $localCachePath . $fileName;
        }
        return null;
    }

    /**
     * Deletes old local cache files from the specified directory if they exceed the configured age limit.
     */
    final public function deleteOldLocalCacheFiles(): void
    {
        if ($this->developmentLocalCacheAgeInDays === 0) {
            return;
        }
        $localCachePath = Environment::getVarPath() . '/transient/ce-api/';
        if (!is_dir($localCachePath)) {
            return;
        }
        // Delete old cache files if it's a full dump call and the directory exists
        $maxAgeInSeconds = $this->developmentLocalCacheAgeInDays * 24 * 60 * 60;
        $now = time();
        try {
            $files = GeneralUtility::getFilesInDir($localCachePath, 'json', true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file)) > $maxAgeInSeconds) {
                        unlink($file);
                    }
                }
            }
        } catch (\Exception $e) {
            unset($e);
        }
    }

}
