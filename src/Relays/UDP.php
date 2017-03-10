<?php
/**
 * UDP relay server
 * User: moyo
 * Date: 10/03/2017
 * Time: 12:17 PM
 */

namespace ONS\Relays;

use ONS\Contract\Transfer;
use swoole_server as SocketServer;

class UDP
{
    /**
     * Listen host
     * @var string
     */
    private $lHost = '127.0.0.1';

    /**
     * Listen port
     * @var int
     */
    private $lPort = 12333;

    /**
     * UDP server constructor.
     * @param $host
     * @param $port
     * @param Transfer $transfer
     */
    public function __construct($host, $port, Transfer $transfer)
    {
        $this->lHost = $host;
        $this->lPort = $port;

        $this->transfer = $transfer;
    }

    /**
     * Start UDP server
     */
    public function start()
    {
        $server = new SocketServer($this->lHost, $this->lPort, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

        $server->on('packet', [$this, 'packetIncoming']);

        $server->start();
    }

    /**
     * UDP packet incoming
     * @param SocketServer $server
     * @param $data
     * @param array $client
     */
    public function packetIncoming(SocketServer $server, $data, array $client)
    {
        $this->transfer->sendAsync($data, [$this, 'resultBlackhole']);
    }

    /**
     * Forward result to blackhole
     * @param $response
     */
    public function resultBlackhole($response)
    {
        // Do nothing
    }
}