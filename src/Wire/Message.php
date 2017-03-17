<?php
/**
 * Message defines
 * User: moyo
 * Date: 15/03/2017
 * Time: 4:58 PM
 */

namespace ONS\Wire;

use ONS\Contract\Message as MSGInterface;

class Message implements MSGInterface
{
    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $handle = '';

    /**
     * @var int
     */
    private $genTime = 0;

    /**
     * @var int
     */
    private $attempts = 0;

    /**
     * @var string
     */
    private $payload = '';

    /**
     * @var int
     */
    private $delayMS = 0;

    /**
     * @var mixed
     */
    private $rawMSG = null;

    /**
     * Message constructor.
     * @param $message
     */
    public function __construct($message)
    {
        $this->rawMSG = $message;

        $this->id = $message['msgId'];
        $this->handle = $message['msgHandle'];
        $this->genTime = $message['bornTime'];
        $this->attempts = $message['reconsumeTimes'];
        $this->payload = $message['body'];
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return int
     */
    public function getGenTime()
    {
        return $this->genTime;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param $data
     * @return static
     */
    public function setPayload($data)
    {
        $this->payload = $data;
        return $this;
    }

    /**
     * @param $delayMS
     * @return static
     */
    public function setLater($delayMS)
    {
        $this->delayMS = $delayMS;
        return $this;
    }

    /**
     * @return bool
     */
    public function makeRetry()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function makeDone()
    {
        return false;
    }

    /**
     * @return string
     */
    public function serializePack()
    {
        return msgpack_pack($this->rawMSG);
    }

    /**
     * @param $data
     * @return static
     */
    public static function serializeUnpack($data)
    {
        return new static(msgpack_unpack($data));
    }
}