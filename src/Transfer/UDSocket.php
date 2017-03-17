<?php
/**
 * UDSocket client
 * User: moyo
 * Date: 16/03/2017
 * Time: 3:42 PM
 */

namespace ONS\Transfer;

use ONS\Contract\Message;
use ONS\Contract\Transfer\Queue;
use ONS\Exception\QueueFeatureNotSupportException;
use ONS\Utils\SimplePacket;

class UDSocket extends Base implements Queue
{
    /**
     * UDSocket constructor.
     * @param $target
     */
    public function __construct($target)
    {
        $this->connTarget = 'unix-sock:'.$target;
    }

    /**
     * Prepare some things before work
     */
    public function prepareWorks()
    {
        $this->connType = SWOOLE_SOCK_UNIX_STREAM;
        $this->connAsync = SWOOLE_SOCK_ASYNC;
        $this->connSettings = SimplePacket::SW_CONN_ARGS;

        $this->initConnection();
    }

    /**
     * @param Message $message
     * @param callable $resultProcessor
     */
    public function forward(Message $message, callable $resultProcessor)
    {
        $this->stashUsrCallback($resultProcessor);

        if ($this->connected)
        {
            $this->doSending(SimplePacket::pack($message->serializePack()));
        }
        else
        {
            $this->setWaitingBuffer(SimplePacket::pack($message->serializePack()));
        }
    }

    /**
     * IF receiving data
     * @param $data
     */
    protected function netPacketReceiving($data)
    {
        $this->execUsrCallback($data);
    }

    /**
     * IF reconnected
     */
    protected function netReconnected()
    {
        // do nothing
    }

    /**
     * IF timeout reached
     * @param $stage
     */
    protected function netTimeoutReached($stage)
    {
        $this->execUsrCallback('FAILED: timeout');
    }

    /**
     * @param $data
     * @param callable $resultProcessor
     */
    public function publish($data, callable $resultProcessor)
    {
        throw new QueueFeatureNotSupportException;
    }

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor)
    {
        throw new QueueFeatureNotSupportException;
    }

    /**
     * @param $handle
     * @param callable $resultProcessor
     */
    public function delete($handle, callable $resultProcessor)
    {
        throw new QueueFeatureNotSupportException;
    }
}