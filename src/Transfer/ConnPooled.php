<?php
/**
 * Connection pool manager
 * User: moyo
 * Date: 11/03/2017
 * Time: 1:31 AM
 */

namespace ONS\Transfer;

use ONS\Access\Authorized;
use ONS\Contract\Transfer;

class ConnPooled implements Transfer
{
    /**
     * @var int
     */
    private $connMax = 10;

    /**
     * @var callable
     */
    private $connInitializer = null;

    /**
     * @var array
     */
    private $listIdle = [];

    /**
     * @var int
     */
    private $countBusy = 0;

    /**
     * @param $size
     */
    public function setConnMax($size)
    {
        $this->connMax = $size;
    }

    /**
     * @param callable $creator
     */
    public function setConnInitializer(callable $creator)
    {
        $this->connInitializer = $creator;
    }

    /**
     * @param $data
     * @param callable $responseProcessor
     */
    public function sendAsync($data, callable $responseProcessor)
    {
        $transfer = $this->getIdleConn();
        if ($transfer)
        {
            $this->setConnBusy();
            $transfer->sendAsync($data, function () use ($transfer, $responseProcessor) {
                $this->setIdleConn($transfer);
                call_user_func_array($responseProcessor, func_get_args());
            });
        }
        else
        {
            call_user_func_array($responseProcessor, ['FAILED: CONN BUSY']);
        }
    }

    /**
     * Fetch out conn from idle pool
     * @return Transfer
     */
    private function getIdleConn()
    {
        $conn = array_pop($this->listIdle);
        if ($conn)
        {
            return $conn;
        }
        else
        {
            if ($this->countBusy < $this->connMax)
            {
                return call_user_func($this->connInitializer);
            }
            else
            {
                return null;
            }
        }
    }

    /**
     * Put back conn to idle pool
     * @param Transfer $conn
     */
    private function setIdleConn(Transfer $conn)
    {
        array_unshift($this->listIdle, $conn);
        $this->countBusy --;
    }

    /**
     * Increment busy conn count
     */
    private function setConnBusy()
    {
        $this->countBusy ++;
    }

    /**
     * @param Authorized $authorized
     */
    public function setAuthorized(Authorized $authorized)
    {
        // nothing
    }

    /**
     * @param $producerID
     */
    public function setProducerID($producerID)
    {
        // nothing
    }

    /**
     * @return bool
     */
    public function isConnReady()
    {
        return true;
    }
}