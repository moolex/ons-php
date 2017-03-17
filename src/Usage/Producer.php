<?php
/**
 * ONS Producer
 * User: moyo
 * Date: 15/03/2017
 * Time: 2:06 PM
 */

namespace ONS\Usage;

use ONS\Access\Authorized;
use ONS\Transfer\HTTP;
use ONS\Transfer\Pool;

class Producer
{
    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * Producer constructor.
     * @param Authorized $authorized
     * @param $producerID
     * @param int $connMax
     * @param int $queueMax
     */
    public function __construct(Authorized $authorized, $producerID, $connMax = 2, $queueMax = 1000)
    {
        $this->pool = new Pool();
        $this->pool->setConnInitializer(function () use ($authorized, $producerID) {

            $client = new HTTP();
            $client->setAuthorized($authorized);
            $client->setProducerID($producerID);
            return $client;

        });
        $this->pool->setConnMax($connMax);
        $this->pool->setQueueMax($queueMax);
    }

    /**
     * Call via pool
     */
    public function prepareWorks()
    {
        $this->pool->prepareWorks();
    }

    /**
     * @param $data
     * @param callable $resultProcessor
     */
    public function publish($data, callable $resultProcessor)
    {
        $this->pool->publish($data, $resultProcessor);
    }
}