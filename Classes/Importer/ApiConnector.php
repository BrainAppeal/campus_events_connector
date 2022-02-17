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

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Http\Client;

class ApiConnector
{

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
    private $apiVersion = '2.3';

    /**
     * @param string $data
     * @param array $additionalParams
     * @return string
     */
    private function generateUri($data, $additionalParams)
    {
        $uri = sprintf('/api/%s/%s?ApiKey=%s',  urlencode($this->apiVersion), urlencode($data), urlencode($this->apiKey));

        foreach ($additionalParams as $key => $value) {
            $uri .= sprintf('&%s=%s', urlencode($key), urlencode($value));
        }

        return $uri;
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
        $client = new Client(['base_uri' => $this->baseUri]);
        $response = $client->get($uri);

        return json_decode($response->getBody(), true);
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
     * @return ApiConnector
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
     * @return ApiConnector
     */
    public function setBaseUri($baseUri)
    {
        if (strpos($baseUri, 'http') === false) {
            $baseUri = 'https://' . $baseUri;
        }
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
     * @return ApiConnector
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
        if (isset($response['status']['version']) && $response['status']['version'] == $this->getApiVersion()) {
            return true;
        }

        return false;
    }

}
