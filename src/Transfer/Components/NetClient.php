<?php
/**
 * Network client
 * User: moyo
 * Date: 16/03/2017
 * Time: 3:55 PM
 */

namespace ONS\Transfer\Components;

use ONS\Monitor\Metrics;
use ONS\Monitor\Monitor;
use swoole_client as SocketClient;

trait NetClient
{
    /**
     * @var string
     */
    protected $connTarget = '';

    /**
     * @var int
     */
    protected $connType = SWOOLE_SOCK_TCP;

    /**
     * @var int
     */
    protected $connAsync = SWOOLE_SOCK_ASYNC;

    /**
     * @var array
     */
    protected $connSettings = [];

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var int
     */
    protected $reconnects = 0;

    /**
     * @var SocketClient;
     */
    private $client = null;

    /**
     * @var callable
     */
    private $usrCallback = null;

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
     * Init conn process
     */
    final protected function initConnection()
    {
        $this->connected && $this->reconnects ++;

        $this->connected = false;

        $this->client = null;
        $this->client = new SocketClient($this->connType, $this->connAsync);

        if ($this->connSettings)
        {
            $this->client->set($this->connSettings);
        }

        $this->client->on('connect', [$this, 'ifConnected']);
        $this->client->on('receive', [$this, 'ifReceived']);
        $this->client->on('error', [$this, 'ifError']);
        $this->client->on('close', [$this, 'ifClosed']);

        if (substr($this->connTarget, 0, 10) == 'unix-sock:')
        {
            $this->ifUnixSockProvided(substr($this->connTarget, 10));
        }
        else
        {
            swoole_async_dns_lookup($this->connTarget, [$this, 'ifDNSResolved']);
        }
    }

    /**
     * User callback will triggers when
     * @param callable $callback
     */
    final protected function stashUsrCallback(callable $callback)
    {
        $this->usrCallback = $callback;
    }

    /**
     * @param $response
     */
    final protected function execUsrCallback($response)
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
    final protected function setWaitingBuffer($buffer)
    {
        $this->usrBuffer = $buffer;
    }

    /**
     * @param $data
     */
    final protected function doSending($data)
    {
        $this->setWaitTimeoutKiller();
        $ok = $this->client->send($data);
        if ($ok)
        {
            Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_SUBMIT);
        }
        else
        {
            $this->reconnectLater($data);
        }
    }

    /**
     * ON data receiving
     * @param $data
     */
    abstract protected function netPacketReceiving($data);

    /**
     * ON net reconnected
     */
    abstract protected function netReconnected();

    /**
     * ON timeout reached
     * @param $stage
     */
    abstract protected function netTimeoutReached($stage);

    /**
     * Check and send local buffer to network
     */
    final private function clsWaitingBuffer()
    {
        if ($this->usrBuffer)
        {
            $this->doSending($this->usrBuffer);
        }
        else
        {
            $this->reconnects && $this->netReconnected();
        }

        $this->usrBuffer = null;
    }

    /**
     * Add timeout killer for send-wait
     */
    final private function setWaitTimeoutKiller()
    {
        $this->waitTimeoutKiller = swoole_timer_after($this->timeoutWaitMS, [$this, 'ifWaitTimeoutKillerWake']);
    }

    /**
     * Add timeout killer for connect-wait
     */
    final private function setConnectTimeoutKiller()
    {
        $this->connectTimeoutKiller = swoole_timer_after($this->timeoutConnectMS, [$this, 'ifConnectTimeoutKillerWake']);
    }

    /**
     * Remove timeout killer for send
     */
    final private function disWaitTimeoutKiller()
    {
        if (swoole_timer_exists($this->waitTimeoutKiller))
        {
            swoole_timer_clear($this->waitTimeoutKiller);
        }
    }

    /**
     * Remove timeout killer for connect
     */
    final private function disConnectTimeoutKiller()
    {
        if (swoole_timer_exists($this->connectTimeoutKiller))
        {
            swoole_timer_clear($this->connectTimeoutKiller);
        }
    }

    /**
     * Reconnect to target later
     * @param $stashBuffer
     */
    final private function reconnectLater($stashBuffer = null)
    {
        if ($stashBuffer)
        {
            $this->setWaitingBuffer($stashBuffer);
        }

        swoole_timer_after($this->reconnectWaitMS, function () {
            $this->initConnection();
        });
    }

    /**
     * @param $path
     */
    final private function ifUnixSockProvided($path)
    {
        $this->setConnectTimeoutKiller();
        $ok = $this->client->connect($path);
        if (!$ok)
        {
            $this->reconnectLater();
        }
    }

    /**
     * @param $host
     * @param $ip
     */
    final public function ifDNSResolved($host, $ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP))
        {
            $this->setConnectTimeoutKiller();
            $ok = $this->client->connect($ip, 80);
            if (!$ok)
            {
                $this->reconnectLater();
            }
        }
        else
        {
            $this->reconnectLater();
        }
    }

    /**
     * @param SocketClient $client
     */
    final public function ifConnected(SocketClient $client)
    {
        $this->disConnectTimeoutKiller();
        $this->connected = true;
        $this->clsWaitingBuffer();
    }


    /**
     * @param SocketClient $client
     * @param $data
     */
    final public function ifReceived(SocketClient $client, $data)
    {
        $this->disWaitTimeoutKiller();
        $this->netPacketReceiving($data);
    }

    /**
     * @param SocketClient $client
     */
    final public function ifError(SocketClient $client)
    {
        $this->reconnectLater();
    }

    /**
     * @param SocketClient $client
     */
    final public function ifClosed(SocketClient $client)
    {
        $this->initConnection();
    }


    /**
     * Reconnect if timeout reached
     */
    final public function ifConnectTimeoutKillerWake()
    {
        Monitor::ctx()->metricIncr(Metrics::NET_CONNECT_TIMEOUT);
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_TIMEOUT);

        $this->initConnection();

        $this->netTimeoutReached('CONNECT');
    }

    /**
     * Reconnect if timeout reached
     */
    final public function ifWaitTimeoutKillerWake()
    {
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_TIMEOUT);

        if ($this->client->isConnected())
        {
            $this->client->close();
        }

        $this->netTimeoutReached('SEND');
    }
}