<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataCollection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiLimitReachedException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiRecordNotFoundException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ApiUnreachableException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Class AbstractApiConnector
 *
 * Provides functionality for interacting with an external API using a configurable set of parameters.
 * This class enables retrieval of data for specific sources, handles caching for API responses,
 * manages API limits, and parses memory limits for large-scale operations.
 */
abstract class AbstractApiConnector
{

    protected int $countApiCalls = 0;
    protected int $countCachedApiCalls = 0;
    protected int $maxApiCallsPerRun = -1;

    protected ?DevelopmentConnectorCache $developmentConnectorCache = null;

    protected string $requestMethod = 'GET';

    protected string $apiKeyHeaderName = 'Authorization';

    public function __construct(
        private readonly LoggerInterface $logger,
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        int $developmentLocalCacheAgeInDays = 0,
        ?string $developmentLocalCacheRelativePath = null
    ) {
        if ($developmentLocalCacheAgeInDays > 0 && $developmentLocalCacheRelativePath && Environment::getContext()->isDevelopment()) {
            $this->developmentConnectorCache = new DevelopmentConnectorCache($this->logger, $developmentLocalCacheRelativePath, $developmentLocalCacheAgeInDays);
        }
    }

    /**
     * @param ?array $additionalHeaders Optional additional headers to include in the request.
     * @return array Returns an array of client options, including the headers.
     */
    protected function getClientOptions(?array $additionalHeaders = null): array
    {
        $headers = [];
        if ($this->apiKeyHeaderName) {
            $headers[$this->apiKeyHeaderName] = $this->apiKey;
        }
        if (!empty($additionalHeaders)) {
            $headers = array_merge($headers, $additionalHeaders);
        }
        $clientOptions = [];
        if (!empty($headers)) {
            $clientOptions['headers'] = $headers;
        }
        return $clientOptions;
    }

    /**
     * Sends an HTTP request to the specified API URL.
     *
     * @param string $apiUrl The URL of the API endpoint to send the request to.
     * @param ?array $additionalHeaders Optional additional headers to include in the request.
     * @return ResponseInterface The response returned from the API.
     */
    protected function sendRequest(string $apiUrl, ?array $additionalHeaders = null): ResponseInterface
    {
        $requestMethod = $this->requestMethod;
        $clientOptions = $this->getClientOptions($additionalHeaders);
        return (new Client())->request($requestMethod, $apiUrl, $clientOptions);
    }

    /**
     * @param string $apiUrlPath The path to be appended to the base URL.
     * @return string The full API URL constructed by combining the base URL and the specified path.
     */
    protected function createApiUrl(string $apiUrlPath): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($apiUrlPath, '/');
    }

    /**
     * Retrieves data from an external API based on the specified api url path
     *
     * @param string $apiUrlPath The relative path for the API endpoint
     * @param array|null $additionalHeaders
     * @param bool $isJsonResponse
     * @return array|string|null The API response data as an associative array, or null if no response is available.
     *
     * @throws ApiLimitReachedException If the maximum number of API calls per run is exceeded.
     * @throws ApiRecordNotFoundException If the response contains an error message.
     */
    protected function getApiResponseForPath(string $apiUrlPath, ?array $additionalHeaders = null, bool $isJsonResponse = true): array|string|null
    {
        if ($this->maxApiCallsPerRun > 0 && $this->countApiCalls >= $this->maxApiCallsPerRun) {
            throw new ApiLimitReachedException(sprintf('Maximum number of API calls reached (%d).', $this->maxApiCallsPerRun));
        }
        // Currently only supports GET requests. This is used to prepare support for sending authentication in the header
        $apiUrl = $this->createApiUrl($apiUrlPath);
        ++$this->countApiCalls;

        $localCacheFilePath = null;
        // Use locally cache files in development environment to prevent excessive API usage
        if ($this->developmentConnectorCache) {
            $localCacheFilePath = $this->developmentConnectorCache->getLocalCachePathForDevelopment($apiUrlPath, $additionalHeaders);
            if ($localResponse = $this->developmentConnectorCache->getLocalResponseCachedFile($localCacheFilePath, $isJsonResponse)) {
                ++$this->countCachedApiCalls;
                return $localResponse;
            }
        }

        try {
            $response = $this->sendRequest($apiUrl, $additionalHeaders);
            if ($response->getStatusCode() === 200) {
                $responseContent = $response->getBody()->getContents();
                if ($isJsonResponse) {
                    $result = json_decode($responseContent, true);
                } else {
                    $result = $responseContent;
                }
                if (!$result) {
                    $this->logger->error(sprintf(
                        'API request for URL "%s" returned status code %d but was empty or invalid JSON',
                        $apiUrl,
                        $response->getStatusCode()
                    ));
                    return null;
                }
                if ($isJsonResponse && array_key_exists('error', $result)) {
                    $errorMessage = sprintf(
                        'API request for URL "%s" returned status code %d but was invalid: %s',
                        $apiUrl,
                        $response->getStatusCode(),
                        $result['error']
                    );
                    $this->logger->error($errorMessage);
                    throw new ApiRecordNotFoundException($errorMessage);
                }
                // Write to local dev cache only if content is not empty and in the development context
                if (!empty($responseContent) && $localCacheFilePath
                    && file_put_contents($localCacheFilePath, $responseContent) === false) {
                    $this->logger->warning(sprintf('Failed to write to local cache file: %s', $localCacheFilePath));
                }
                return $result;
            }

            $this->logger->error(sprintf(
                'API request for URL "%s" failed with status code %d: %s',
                $apiUrl,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
            return null;
        } catch (ApiRecordNotFoundException $e) {
            throw $e;
        } catch (RequestException $e) {
            $message = sprintf(
                'API request for URL "%s" failed with Guzzle exception: %s',
                $apiUrl,
                $e->getMessage()
            );
            $this->logger->error($message);
            if (($response = $e->getResponse()) && $response->getStatusCode() === 404) {
                return null;
            }
            throw new ApiUnreachableException($message, 1735485432, $e);
        } catch (\Throwable $e) {
            // Catch any other potential errors during the API call or processing
            $message = sprintf(
                'An unexpected error occurred during API request or processing for URL "%s": %s',
                $apiUrl,
                $e->getMessage()
            );
            $this->logger->error($message);
            throw new ApiUnreachableException($message, 1735485433, $e);
        }
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
     * @param string $baseUrl
     * @return static
     */
    public function setBaseUrl(string $baseUrl): static
    {
        if (!str_contains($baseUrl, 'http')) {
            $baseUrl = 'https://' . $baseUrl;
        }
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Sets the maximum number of API calls allowed per run.
     *
     **/
    public function setMaxApiCallsPerRun(int $maxApiCallsPerRun): void
    {
        $this->maxApiCallsPerRun = $maxApiCallsPerRun;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Retrieves the total count of API calls made.
     *
     * @return int The number of API calls made.
     */
    public function getCountApiCalls(): int
    {
        return $this->countApiCalls;
    }

    /**
     * Retrieves the count of API calls that have been retrieved from the cache.
     *
     * @return int The number of cached API calls.
     */
    public function getCountCachedApiCalls(): int
    {
        return $this->countCachedApiCalls;
    }

    public function __destruct()
    {
        $this->developmentConnectorCache?->deleteOldLocalCacheFiles();
    }
}
