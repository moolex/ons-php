<?php
/**
 * Async HTTP Client
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:26 PM
 */

namespace ONS\Transfer;

use ONS\Contract\Transfer\Property;
use ONS\Contract\Transfer\Queue;
use ONS\Exception\QueueFeatureNotSupportException;
use ONS\Monitor\Metrics;
use ONS\Monitor\Monitor;
use ONS\Utils\HTT2Proto;
use ONS\Wire\HTTP as Wire;
use ONS\Wire\Message;
use ONS\Wire\Results\Messages as ResultMessages;

class HTTP extends Base implements Property, Queue
{
    /**
     * @var string
     */
    private $userAgent = 'ons-php:async-http/1.0';

    /**
     * @var Wire
     */
    private $wire = null;

    /**
     * @var bool
     */
    private $consuming = false;

    /**
     * @var callable
     */
    private $msgCallback = null;

    /**
     * @var bool
     */
    private $recvIsWaiting = false;

    /**
     * @var int
     */
    private $recvExpectSize = 0;

    /**
     * @var int
     */
    private $recvFoundSize = 0;

    /**
     * @var string
     */
    private $recvBuffer = '';

    /**
     * Prepare some things before work
     */
    public function prepareWorks()
    {
        $this->wire = new Wire($this->authorized, $this->userAgent);

        $this->connTarget = $this->authorized->getEndpoint();
        $this->connType = SWOOLE_SOCK_TCP;
        $this->connAsync = SWOOLE_SOCK_ASYNC;

        $this->initConnection();
    }

    /**
     * @param $data
     * @param callable $resultProcessor
     */
    public function publish($data, callable $resultProcessor)
    {
        $this->trySending($resultProcessor, $this->wire->publish($this->producerID, $data));
    }

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor)
    {
        $this->msgCallback = $messageProcessor;

        $this->consuming = true;

        $this->msgLoopingTick();
    }

    /**
     * @param $handle
     * @param callable $resultProcessor
     */
    public function delete($handle, callable $resultProcessor)
    {
        $this->trySending($resultProcessor, $this->wire->delete($this->consumerID, $handle));
    }

    /**
     * NOT SUPPORT FOR HTTP
     * @param \ONS\Contract\Message $message
     * @param callable $resultProcessor
     */
    public function forward(\ONS\Contract\Message $message, callable $resultProcessor)
    {
        throw new QueueFeatureNotSupportException;
    }

    /**
     * @param callable $usrCallback
     * @param $buffer
     */
    private function trySending(callable $usrCallback, $buffer)
    {
        $this->stashUsrCallback($usrCallback);
        $this->connected ? $this->doSending($buffer) : $this->setWaitingBuffer($buffer);
    }

    /**
     * Tick msg looping
     */
    private function msgLoopingTick()
    {
        $query = $this->wire->subscribe($this->consumerID);
        $this->connected ? $this->doSending($query) : $this->setWaitingBuffer($query);
    }

    /**
     * @param $response
     */
    private function msgInsGenerator($response)
    {
        $result = $this->wire->result($response);
        if ($result instanceof ResultMessages)
        {
            $messages = $result->gets();
            foreach ($messages as $message)
            {
                call_user_func_array($this->msgCallback, [new Message($message)]);
            }

            if (empty($messages))
            {
                swoole_timer_after($this->intervalPollMS, function () {
                    $this->msgLoopingTick();
                });
            }
            else
            {
                $this->msgLoopingTick();
            }
        }
    }

    /**
     * IF receive data
     * @param $data
     */
    protected function netPacketReceiving($data)
    {
        $recvDONE = false;
        $recvDATA = '';
        if ($this->recvIsWaiting)
        {
            $this->recvBuffer .= $data;
            $this->recvFoundSize += strlen($data);

            if ($this->recvFoundSize >= $this->recvExpectSize)
            {
                $recvDONE = true;
                $recvDATA = $this->recvBuffer;

                $this->recvIsWaiting = false;
            }
        }
        else
        {
            $parsed = HTT2Proto::parseResponse($data);
            if (is_array($parsed))
            {
                if ($parsed['size'] <= strlen($parsed['body']))
                {
                    $recvDONE = true;
                    $recvDATA = $data;
                }
                else
                {
                    $this->recvIsWaiting = true;
                    $this->recvExpectSize = $parsed['size'];
                    $this->recvFoundSize = strlen($parsed['body']);
                    $this->recvBuffer = $data;
                }
            }
        }

        if ($recvDONE)
        {
            if ($this->consuming)
            {
                $this->msgInsGenerator($recvDATA);
            }
            else
            {
                $this->execUsrCallback($this->wire->result($recvDATA));
            }
        }
    }

    /**
     * IF reconnected
     */
    protected function netReconnected()
    {
        if ($this->consuming)
        {
            $this->msgLoopingTick();
        }
    }

    /**
     * @param $stage
     */
    protected function netTimeoutReached($stage)
    {
        $this->execUsrCallback($this->wire->result(['code' => 504, 'body' => $stage]));
    }
}