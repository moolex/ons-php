<?php
/**
 * Async HTTP Client
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:26 PM
 */

namespace ONS\Transfer;

use ONS\Contract\Transfer;
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
     * @param $data
     * @param callable $responseProcessor
     */
    public function sendAsync($data, callable $responseProcessor)
    {
        $this->setConnBusy();
        $this->initConnection();
        $this->stashCallback($responseProcessor);

        if ($this->connected)
        {
            $this->client->send($this->makeHTTP($data));
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
        if (!$this->connected)
        {
            $this->client = null;
            $this->client = new SocketClient(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

            $this->client->on('connect', [$this, 'ifConnected']);
            $this->client->on('receive', [$this, 'ifReceived']);
            $this->client->on('error', [$this, 'ifError']);
            $this->client->on('close', [$this, 'ifClosed']);

            swoole_async_dns_lookup($this->authorized->getEndpoint(), [$this, 'ifDNSResolved']);
        }
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
        if (is_callable($this->usrCallback))
        {
            call_user_func_array($this->usrCallback, [$response, $this]);
        }

        $this->usrCallback = null;
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
            $this->client->send($this->usrBuffer);
        }

        $this->usrBuffer = null;
    }

    /**
     * @param $host
     * @param $ip
     */
    public function ifDNSResolved($host, $ip)
    {
        $this->client->connect($ip, 80);
    }

    /**
     * @param SocketClient $client
     */
    public function ifConnected(SocketClient $client)
    {
        $this->connected = true;
        $this->setConnIdle();
        $this->clearSending();
    }

    /**
     * @param SocketClient $client
     * @param $data
     */
    public function ifReceived(SocketClient $client, $data)
    {
        $this->setConnIdle();
        $this->execCallback($data);
    }

    /**
     * @param SocketClient $client
     */
    public function ifError(SocketClient $client)
    {
        $this->connected = false;
        $this->initConnection();
    }

    /**
     * @param SocketClient $client
     */
    public function ifClosed(SocketClient $client)
    {
        $this->connected = false;
        $this->initConnection();
    }
}