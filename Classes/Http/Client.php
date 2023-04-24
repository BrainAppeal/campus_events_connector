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

namespace BrainAppeal\CampusEventsConnector\Http;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\HttpRequest;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Client
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /** @var array Default request options */
    private $config;

    /**
     * @var bool
     */
    private static $isGuzzleAvailable;

    /**
     * @return bool
     */
    private function isGuzzleAvailable()
    {
        if (null === self::$isGuzzleAvailable) {
            self::$isGuzzleAvailable = class_exists('\GuzzleHttp\Client');
        }
        return self::$isGuzzleAvailable;
    }

    /**
     * Clients accept an array of constructor parameters.
     *
     * Here's an example of creating a client using a base_uri and an array of
     * default request options to apply to each request:
     *
     *     $client = new Client([
     *         'base_uri'        => 'http://www.foo.com/1.0/',
     *         'timeout'         => 0,
     *         'allow_redirects' => false,
     *         'proxy'           => '192.168.16.1:10'
     *     ]);
     *
     * Client configuration settings include the following options:
     *
     * - handler: (callable) Function that transfers HTTP requests over the
     *   wire. The function is called with a Psr7\Http\Message\RequestInterface
     *   and array of transfer options, and must return a
     *   GuzzleHttp\Promise\PromiseInterface that is fulfilled with a
     *   Psr7\Http\Message\ResponseInterface on success. "handler" is a
     *   constructor only option that cannot be overridden in per/request
     *   options. If no handler is provided, a default handler will be created
     *   that enables all the request options below by attaching all the
     *   default middleware to the handler.
     * - base_uri: (string|UriInterface) Base URI of the client that is merged
     *   into relative URIs. Can be a string or instance of UriInterface.
     * - **: any request option
     *
     * @param array $config Client configuration settings.
     *
     * @see \GuzzleHttp\RequestOptions for a list of available request options.
     */
    public function __construct(array $config = [])
    {
        if ($this->isGuzzleAvailable()) {
            $this->client = new \GuzzleHttp\Client($config);
        } else {
            $this->config = $config;
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function getAbsUrl($url)
    {
        if (strpos($url, '/') === 0) {
            $url = rtrim($this->config['base_uri'],'/') . $url;
        }

        return $url;
    }

    /**
     * @param array $options
     * @return array
     */
    private function filterRequestOptions(&$options)
    {
        $filteredOptions=[];
        if (isset($options['sink'])) {
            $filteredOptions['sink'] = $options['sink'];
            unset($options['sink']);
        }

        return $filteredOptions;
    }

    /**
     * @param string|UriInterface $uri
     * @param array $options
     * @return ResponseInterface
     */
    public function get($uri, $options = [])
    {
        if ($this->isGuzzleAvailable()) {
            try {
                $response = $this->client->get($uri, $options);
            } catch (GuzzleException|\Throwable $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $filteredOptions = $this->filterRequestOptions($options);
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

            try {
                $response = $requestFactory->request(
                    $this->getAbsUrl($uri),
                    'GET',
                    $filteredOptions
                );
                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException(
                        'Returned status code is ' . $response->getStatusCode()
                    );
                }
            } catch (\Throwable $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        }
        return $response;
    }

    /**
     * @param string|UriInterface $uri
     * @param array $options
     * @return PromiseInterface
     */
    public function getAsync($uri, $options = [])
    {
        if ($this->isGuzzleAvailable()) {
            try {
                $promise = $this->client->getAsync($uri, $options);
                $promise = new GuzzlePromise($promise);
            } catch (GuzzleException|\Throwable $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $promise = new Promise($this, $uri, $options);
        }
        return $promise;
    }
}
