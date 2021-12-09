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

use TYPO3\CMS\Core\Http\HttpRequest;

class Promise implements PromiseInterface
{
    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \HTTP_Request2_Response
     */
    private $response;

    /**
     * Promise constructor.
     * @param Client $client
     * @param string $uri
     * @param array $options
     */
    public function __construct($client, $uri, $options)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->options = $options;
    }

    /**
     * @throws HttpException
     */
    public function wait()
    {
        $this->response = $this->client->get($this->uri, $this->options);
    }

    public function getState()
    {
        if (null === $this->response) {
            return self::PENDING;
        }
        if ($this->response->getStatus() == 200) {
            return self::FULFILLED;
        }
        return self::REJECTED;
    }
}
