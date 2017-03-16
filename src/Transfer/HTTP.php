<?php
/**
 * Async HTTP Client
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:26 PM
 */

namespace ONS\Transfer;

use ONS\Contract\Transfer;
use ONS\Monitor\Metrics;
use ONS\Monitor\Monitor;
use ONS\Utils\HTT2Proto;
use ONS\Wire\HTTP as Wire;
use ONS\Wire\Message;
use ONS\Wire\Results\Messages as ResultMessages;
use swoole_client as SocketClient;

class HTTP extends AbstractBase implements Transfer
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
     * @var bool
     */
    private $connected = false;

    /**
     * @var int
     */
    private $reconnects = 0;

    /**
     * @var SocketClient;
     */
    private $client = null;

    /**
     * @var callable
     */
    private $usrCallback = null;

    /**
     * @var callable
     */
    private $msgCallback = null;

    /**
     * @var string
     */
    private $usrBuffer = null;

    /**
     * @var int
     */
    private $connectTimeoutKiller = null;

    /**
     * @var int
     */
    private $waitTimeoutKiller = null;

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

        $this->initConnection();
    }

    /**
     * @param $data
     * @param callable $responseProcessor
     */
    public function publish($data, callable $responseProcessor)
    {
        $this->stashUsrCallback($responseProcessor);

        if ($this->connected)
        {
            $this->doSending($this->wire->publish($this->producerID, $data));
        }
        else
        {
            $this->setWaitingBuffer($this->wire->publish($this->producerID, $data));
        }
    }

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor)
    {
        $this->stashMsgCallback($messageProcessor);

        $this->consuming = true;

        $this->msgLoopingTick();
    }

    /**
     * Tick msg looping
     */
    private function msgLoopingTick()
    {
        if ($this->connected)
        {
            $this->doSending($this->wire->subscribe($this->consumerID));
        }
        else
        {
            $this->setWaitingBuffer($this->wire->subscribe($this->consumerID));
        }
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
                swoole_timer_after($this->timeoutPollMS, function () {
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
     * Check and init connection
     */
    private function initConnection()
    {
        $this->connected && $this->reconnects ++;

        $this->connected = false;

        $this->client = null;
        $this->client = new SocketClient(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $this->client->on('connect', [$this, 'ifConnected']);
        $this->client->on('receive', [$this, 'ifReceived']);
        $this->client->on('error', [$this, 'ifError']);
        $this->client->on('close', [$this, 'ifClosed']);

        swoole_async_dns_lookup($this->authorized->getEndpoint(), [$this, 'ifDNSResolved']);
    }

    /**
     * @param callable $callback
     */
    private function stashUsrCallback(callable $callback)
    {
        $this->usrCallback = $callback;
    }

    /**
     * @param callable $callback
     */
    private function stashMsgCallback(callable $callback)
    {
        $this->msgCallback = $callback;
    }

    /**
     * @param $response
     */
    private function execUsrCallback($response)
    {
        $processor = $this->usrCallback;

        $this->usrCallback = null;

        if (is_callable($processor))
        {
            call_user_func_array($processor, [$response, $this]);
        }
    }

    /**
     * Set waiting buffer for next send if we connected
     * @param $buffer
     */
    private function setWaitingBuffer($buffer)
    {
        $this->usrBuffer = $buffer;
    }

    /**
     * Check and send local buffer to network
     */
    private function clsWaitingBuffer()
    {
        if ($this->usrBuffer)
        {
            $this->doSending($this->usrBuffer);
        }
        else
        {
            if ($this->consuming && $this->reconnects)
            {
                $this->msgLoopingTick();
            }
        }

        $this->usrBuffer = null;
    }

    /**
     * @param $data
     */
    private function doSending($data)
    {
        $this->setWaitTimeoutKiller();
        $this->client->send($data);
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_SUBMIT);
    }

    /**
     * Add timeout killer for send-wait
     */
    private function setWaitTimeoutKiller()
    {
        $this->waitTimeoutKiller = swoole_timer_after($this->timeoutWaitMS, [$this, 'ifWaitTimeoutKillerWake']);
    }

    /**
     * Add timeout killer for connect-wait
     */
    private function setConnectTimeoutKiller()
    {
        $this->connectTimeoutKiller = swoole_timer_after($this->timeoutConnectMS, [$this, 'ifConnectTimeoutKillerWake']);
    }

    /**
     * Remove timeout killer for send
     */
    private function disWaitTimeoutKiller()
    {
        swoole_timer_clear($this->waitTimeoutKiller);
    }

    /**
     * Remove timeout killer for connect
     */
    private function disConnectTimeoutKiller()
    {
        swoole_timer_clear($this->connectTimeoutKiller);
    }

    /**
     * Reconnect if timeout reached
     */
    public function ifConnectTimeoutKillerWake()
    {
        Monitor::ctx()->metricIncr(Metrics::NET_CONNECT_TIMEOUT);
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_TIMEOUT);

        $this->initConnection();

        $this->execUsrCallback('FAILED: CONNECT TIMEOUT');
    }

    /**
     * Reconnect if timeout reached
     */
    public function ifWaitTimeoutKillerWake()
    {
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_TIMEOUT);

        if ($this->client->isConnected())
        {
            $this->client->close();
        }

        $this->execUsrCallback('FAILED: WAIT TIMEOUT');
    }

    /**
     * @param $host
     * @param $ip
     */
    public function ifDNSResolved($host, $ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP))
        {
            $this->setConnectTimeoutKiller();
            $this->client->connect($ip, 80);
        }
        else
        {
            $this->initConnection();
        }
    }

    /**
     * @param SocketClient $client
     */
    public function ifConnected(SocketClient $client)
    {
        $this->disConnectTimeoutKiller();
        $this->connected = true;
        $this->clsWaitingBuffer();
    }

    /**
     * @param SocketClient $client
     * @param $data
     */
    public function ifReceived(SocketClient $client, $data)
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
            $this->disWaitTimeoutKiller();

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
            $this->consuming ? $this->msgInsGenerator($recvDATA) : $this->execUsrCallback($recvDATA);

            Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_RESPONSE);
        }
    }

    /**
     * @param SocketClient $client
     */
    public function ifError(SocketClient $client)
    {
        $this->initConnection();
    }

    /**
     * @param SocketClient $client
     */
    public function ifClosed(SocketClient $client)
    {
        $this->initConnection();
    }
}