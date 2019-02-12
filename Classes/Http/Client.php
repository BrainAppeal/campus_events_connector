<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-24
 */

namespace BrainAppeal\CampusEventsConnector\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\HttpRequest;

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
    private static $isGuzzleAvailable = null;

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
     *   that enables all of the request options below by attaching all of the
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
     * @return \HTTP_Request2_Response|ResponseInterface
     */
    public function get($uri, $options = [])
    {
        if ($this->isGuzzleAvailable()) {
            try {
                $response = $this->client->get($uri, $options);
            } catch (\Exception $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $filteredOptions = $this->filterRequestOptions($options);
            /** @var $request HttpRequest */
            $request = new HttpRequest($this->getAbsUrl($uri), HttpRequest::METHOD_GET, $options);
            try {
                if (isset($filteredOptions['sink'])) {
                    $response = $request->download(dirname($filteredOptions['sink']), basename($filteredOptions['sink']));
                } else {
                    $response = $request->send();
                }
            } catch (\Exception $e) {
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
            } catch (\Exception $e) {
                throw new HttpException($e->getMessage(), $e->getCode(), $e);
            }
        } else {
            $promise = new Promise($this, $uri, $options);
        }
        return $promise;
    }
}