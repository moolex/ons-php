<?php
/**
 * Base property set
 * User: moyo
 * Date: 16/03/2017
 * Time: 4:03 PM
 */

namespace ONS\Transfer\Components;

use ONS\Access\Authorized;
use ONS\Contract\Transfer\Property;

abstract class PropertySet implements Property
{
    /**
     * @var Authorized
     */
    protected $authorized = null;

    /**
     * @var string
     */
    protected $producerID = '';

    /**
     * @var string
     */
    protected $consumerID = '';

    /**
     * @var int
     */
    protected $timeoutConnectMS = 1000;

    /**
     * @var int
     */
    protected $timeoutWaitMS = 1000;

    /**
     * @var int
     */
    protected $reconnectWaitMS = 1500;

    /**
     * @var int
     */
    protected $intervalPollMS = 500;

    /**
     * @param Authorized $authorized
     */
    final public function setAuthorized(Authorized $authorized)
    {
        $this->authorized = $authorized;
    }

    /**
     * @param $producerID
     */
    final public function setProducerID($producerID)
    {
        $this->producerID = $producerID;
    }

    /**
     * @param $consumerID
     */
    final public function setConsumerID($consumerID)
    {
        $this->consumerID = $consumerID;
    }

    /**
     * @param $ms
     */
    final public function setTimeoutConnect($ms)
    {
        $this->timeoutConnectMS = $ms;
    }

    /**
     * @param $ms
     */
    final public function setTimeoutWait($ms)
    {
        $this->timeoutWaitMS = $ms;
    }

    /**
     * @param $ms
     */
    final public function setReconnectWait($ms)
    {
        $this->reconnectWaitMS = $ms;
    }

    /**
     * @param $ms
     */
    final public function setIntervalPoll($ms)
    {
        $this->intervalPollMS = $ms;
    }
}