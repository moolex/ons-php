<?php
/**
 * Abstract base for transfer
 * User: moyo
 * Date: 10/03/2017
 * Time: 3:21 PM
 */

namespace ONS\Transfer;

use ONS\Access\Authorized;
use ONS\Contract\Transfer;

abstract class AbstractBase implements Transfer
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
     * @var bool
     */
    protected $cIsReady = false;

    /**
     * @param Authorized $authorized
     */
    public function setAuthorized(Authorized $authorized)
    {
        $this->authorized = $authorized;
    }

    /**
     * @param $producerID
     */
    public function setProducerID($producerID)
    {
        $this->producerID = $producerID;
    }

    /**
     * @return bool
     */
    public function isConnReady()
    {
        return $this->cIsReady;
    }

    /**
     * Set connection is idle
     */
    protected function setConnIdle()
    {
        $this->cIsReady = true;
    }

    /**
     * Set connection is busy
     */
    protected function setConnBusy()
    {
        $this->cIsReady = false;
    }
}