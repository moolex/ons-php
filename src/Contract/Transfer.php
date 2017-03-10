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
     * @return bool
     */
    public function isConnReady();

    /**
     * @param $data
     * @param callable $responseProcessor
     */
    public function sendAsync($data, callable $responseProcessor);
}