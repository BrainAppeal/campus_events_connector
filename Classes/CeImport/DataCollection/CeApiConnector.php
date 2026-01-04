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

namespace BrainAppeal\CampusEventsConnector\CeImport\DataCollection;

use BrainAppeal\CampusEventsConnector\CeImport\DataTransformer\DefaultDataTransformer;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiLimitReachedException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiRecordNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\DataCollection\AbstractApiConnector;
use Psr\Log\LoggerInterface;

class CeApiConnector extends AbstractApiConnector
{
    public const DEV_RELATIVE_CACHE_PATH = 'ce-api-test';

    public const BASE_PATH = '/api/';

    public const ID_FIELD = '@id';

    protected string $apiKeyHeaderName = 'X-API-KEY';

    /**
     * @var array
     */
    protected array $apiTypeMapping = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
        parent::__construct($this->logger, null, null, 14, self::DEV_RELATIVE_CACHE_PATH);
    }

    /**
     * @param string $relativeUrl
     * @param array<string, string> $additionalParams Additional url parameters
     * @return string
     */
    private function generateUri(string $relativeUrl, array $additionalParams): string
    {
        if (str_starts_with($relativeUrl, self::BASE_PATH)) {
            $url = $relativeUrl;
        } else {
            $url = self::BASE_PATH . $relativeUrl;
        }
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
     * @param string|null $languageCode
     * @return array
     * @throws ApiLimitReachedException
     * @throws ApiRecordNotFoundException
     */
    public function getApiResponse(string $relativeUrl, array $additionalParams = [], ?string $languageCode = null): array
    {
        $additionalHeaders = null;
        if (!empty($languageCode)) {
            $additionalHeaders = ['Accept-Language' => $languageCode];
        }
        $uri = $this->generateUri($relativeUrl, $additionalParams);
        return $this->getApiResponseForPath($uri, $additionalHeaders);
    }

    /**
     * @return bool
     * @throws ApiLimitReachedException
     * @throws ApiRecordNotFoundException
     */
    public function checkApiAccess(): bool
    {
        $response = $this->getApiResponse('events');
        return !empty($response) && !empty($response['@id']);
    }

    /**
     * Fetches a list of items for the specified type by making API calls and processing paginated responses.
     *
     * @param DefaultDataTransformer $dataTransformer
     * @return array A list of items retrieved from the API, or an empty array if no items are found or the type mapping is invalid.
     */
    public function fetchItemListForType(DefaultDataTransformer $dataTransformer, ?string $languageCode = null): array
    {
        $itemType = $dataTransformer->getEntityName();
        $path = $dataTransformer->getApiEndpoint();
        $apiResponse = $this->getApiResponse($path, [], $languageCode);
        $allListItems = [];
        if (!empty($apiResponse['hydra:member'])) {
            $this->addListItemsFromResponse($allListItems, $apiResponse);
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
                        $apiResponse = $this->getApiResponse($path . $pageParamPrefix . $nextPage, [], $languageCode);
                        $this->addListItemsFromResponse($allListItems, $apiResponse);
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
     */
    protected function addListItemsFromResponse(array &$allListItems, array $apiResponse): void
    {
        if (!empty($apiResponse['hydra:member'])) {
            foreach ($apiResponse['hydra:member'] as $listItem) {
                $importId = self::filterId($listItem['@id']);
                $listItem['id'] = $importId;
                $allListItems[$importId] = $listItem;
            }
        }
    }

    /**
     * Returns the id for the given reference string
     *
     * @param string $apiReferenceId
     * @return int
     */
    public static function filterId(string $apiReferenceId): int
    {
        if (is_numeric($apiReferenceId)) {
            return (int)$apiReferenceId;
        }
        $parts = explode('/', $apiReferenceId);
        return (int) end($parts);
    }

}
