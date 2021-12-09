<?php

namespace BrainAppeal\CampusEventsConnector\Http;

interface PromiseInterface
{
    /**
     * @throws HttpException
     */
    public function wait();

    public function getState();
}