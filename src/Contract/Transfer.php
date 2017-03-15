<?php
/**
 * Transfer interface
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:30 PM
 */

namespace ONS\Contract;

use ONS\Access\Authorized;

interface Transfer
{
    /**
     * @param Authorized $authorized
     */
    public function setAuthorized(Authorized $authorized);

    /**
     * @param $producerID
     */
    public function setProducerID($producerID);

    /**
     * @param $consumerID
     */
    public function setConsumerID($consumerID);

    /**
     * @param $ms
     */
    public function setTimeoutConnect($ms);

    /**
     * @param $ms
     */
    public function setTimeoutWait($ms);

    /**
     * @return void
     */
    public function prepareWorks();

    /**
     * @param $data
     * @param callable $responseProcessor
     */
    public function publish($data, callable $responseProcessor);

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor);
}