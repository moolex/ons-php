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
use swoole_client as SocketClient;

class AsyncHTTP extends AbstractBase implements Transfer
{
    /**
     * @var string
     */
    private $userAgent = 'relay2ons:async-http/1.0';

    /**
     * @var bool
     */
    private $connected = false;

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
     * Prepare some things before work
     */
    public function prepareWorks()
    {
        $this->initConnection();
    }

    /**
     * @param $data
     * @param callable $responseProcessor
     */
    public function sendAsync($data, callable $responseProcessor)
    {
        $this->stashCallback($responseProcessor);

        if ($this->connected)
        {
            $this->doSending($this->makeHTTP($data));
        }
        else
        {
            $this->waitSending($this->makeHTTP($data));
        }
    }

    /**
     * Generate HTTP Data
     * @param $payload
     * @return string
     */
    private function makeHTTP($payload)
    {
        $topic = $this->authorized->getTopic();

        $date = (int)(microtime(true) * 1000);

        $hash = md5($payload);
        $sample = "{$topic}\n{$this->producerID}\n{$hash}\n{$date}";
        $sign = base64_encode(hash_hmac('sha1', $sample, $this->authorized->getKeySecret(), true));

        $postSize = strlen($payload);

        $data =
            "POST /message/?topic={$topic}&time={$date}&tag=http&key=http HTTP/1.1\n".
            "Host: {$this->authorized->getEndpoint()}\n".
            "User-Agent: {$this->userAgent}\n".
            "AccessKey: {$this->authorized->getKeyID()}\n".
            "ProducerID: {$this->producerID}\n".
            "Signature: {$sign}\n".
            "Content-Type: text/html;charset=UTF-8\n".
            "Content-Length: {$postSize}\n".
            "Connection: keep-alive\n".
            "\n".
            "{$payload}"
            ;

        return $data;
    }

    /**
     * Check and init connection
     */
    private function initConnection()
    {
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
    private function stashCallback(callable $callback)
    {
        $this->usrCallback = $callback;
    }

    /**
     * @param $response
     */
    private function execCallback($response)
    {
        $processor = $this->usrCallback;

        $this->usrCallback = null;

        if (is_callable($processor))
        {
            call_user_func_array($processor, [$response, $this]);
        }
    }

    /**
     * Wait next sending
     * @param $buffer
     */
    private function waitSending($buffer)
    {
        $this->usrBuffer = $buffer;
    }

    /**
     * Check and send now
     */
    private function clearSending()
    {
        if ($this->usrBuffer)
        {
            $this->doSending($this->usrBuffer);
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

        $this->execCallback('FAILED: CONNECT TIMEOUT');
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

        $this->execCallback('FAILED: WAIT TIMEOUT');
    }

    /**
     * @param $host
     * @param $ip
     */
    public function ifDNSResolved($host, $ip)
    {
        $this->setConnectTimeoutKiller();
        $this->client->connect($ip, 80);
    }

    /**
     * @param SocketClient $client
     */
    public function ifConnected(SocketClient $client)
    {
        $this->disConnectTimeoutKiller();
        $this->connected = true;
        $this->clearSending();
    }

    /**
     * @param SocketClient $client
     * @param $data
     */
    public function ifReceived(SocketClient $client, $data)
    {
        $this->disWaitTimeoutKiller();
        $this->execCallback($data);
        Monitor::ctx()->metricIncr(Metrics::MSG_FORWARD_RESPONSE);
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