<?php
/**
 * Transfer property
 * User: moyo
 * Date: 16/03/2017
 * Time: 4:34 PM
 */

namespace ONS\Contract\Transfer;

use ONS\Access\Authorized;

interface Property
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
     * @param $ms
     */
    public function setReconnectWait($ms);

    /**
     * @param $ms
     */
    public function setIntervalPoll($ms);
}