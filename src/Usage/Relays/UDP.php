<?php
/**
 * UDP relay server
 * User: moyo
 * Date: 10/03/2017
 * Time: 12:17 PM
 */

namespace ONS\Usage\Relays;

use ONS\Monitor\Monitor;
use ONS\Usage\Producer;
use swoole_server as SocketServer;

class UDP
{
    /**
     * Listen host
     * @var string
     */
    private $listenHost = '127.0.0.1';

    /**
     * Listen port
     * @var int
     */
    private $listenPort = 12333;

    /**
     * @var int
     */
    private $workersMax = 1;

    /**
     * @var Producer
     */
    private $producer = null;

    /**
     * UDP server constructor.
     * @param Producer $producer
     * @param string $host
     * @param int $port
     * @param int $workersMax
     */
    public function __construct(Producer $producer, $host = '127.0.0.1', $port = 12333, $workersMax = 1)
    {
        $this->producer = $producer;

        $this->listenHost = $host;
        $this->listenPort = $port;

        $this->workersMax = $workersMax;
    }

    /**
     * Start UDP server
     */
    public function start()
    {
        $server = new SocketServer($this->listenHost, $this->listenPort, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

        $server->set([
            'worker_num' => $this->workersMax,
            'dispatch_mode' => 3
        ]);

        $server->on('workerStart', [$this, 'mgrWorkerStart']);
        $server->on('packet', [$this, 'packetIncoming']);

        Monitor::init($this->workersMax);
        $server->start();
    }

    /**
     * @param SocketServer $server
     * @param $workerID
     */
    public function mgrWorkerStart(SocketServer $server, $workerID)
    {
        Monitor::prepare($workerID);
        $this->producer->prepareWorks();
    }

    /**
     * UDP packet incoming
     * @param SocketServer $server
     * @param $data
     * @param array $client
     */
    public function packetIncoming(SocketServer $server, $data, array $client)
    {
        $this->producer->publish($data, [$this, 'resultBlackhole']);
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