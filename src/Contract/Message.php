<?php
/**
 * Message interface
 * User: moyo
 * Date: 15/03/2017
 * Time: 2:17 PM
 */

namespace ONS\Contract;

interface Message
{
    /**
     * @return string
     */
    public function getID();

    /**
     * @return string
     */
    public function getHandle();

    /**
     * @return int
     */
    public function getGenTime();

    /**
     * @return int
     */
    public function getAttempts();

    /**
     * @return string
     */
    public function getPayload();

    /**
     * @param $data
     * @return static
     */
    public function setPayload($data);

    /**
     * @param $delayMS
     * @return static
     */
    public function setLater($delayMS);

    /**
     * @return bool
     */
    public function makeRetry();

    /**
     * @return bool
     */
    public function makeDone();

    /**
     * @return string
     */
    public function serializePack();

    /**
     * @param $data
     * @return static
     */
    public static function serializeUnpack($data);
}