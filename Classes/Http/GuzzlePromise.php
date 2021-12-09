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

class GuzzlePromise implements PromiseInterface
{
    /**
     * @var \GuzzleHttp\Promise\Promise
     */
    private $promise;

    public function __construct($promise) {
        $this->promise = $promise;
    }

    public function wait()
    {
        try {
            $promise = $this->promise->wait();
        } catch (GuzzleException|\Throwable $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getState()
    {
        $this->promise->getState();
    }

    public function __call($name, $arguments) {
        if (method_exists($this->promise, $name)) {
            return call_user_func_array(array($this->promise, $name), $arguments);
        }
        throw new \BadMethodCallException('Call to undefined method ' . static::class . "::$name()", 0);
    }
}
