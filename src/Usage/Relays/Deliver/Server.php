<?php
/**
 * Deliver server (recv)
 * User: moyo
 * Date: 16/03/2017
 * Time: 3:09 PM
 */

namespace ONS\Usage\Relays\Deliver;

use ONS\Monitor\Monitor;
use ONS\Utils\SimplePacket;
use swoole_server as SocketServer;

abstract class Server
{
    /**
     * @var string
     */
    private $unixSock = '/tmp/deliver-server.sock';

    /**
     * @var int
     */
    private $workersMax = 1;

    /**
     * @param $uds
     */
    final public function setUnixSock($uds)
    {
        $this->unixSock = $uds;
    }

    /**
     * @param $max
     */
    final public function setWorkersMax($max)
    {
        $this->workersMax = $max;
    }

    /**
     * Start UDP server
     */
    final public function start()
    {
        $server = new SocketServer($this->unixSock, null, SWOOLE_PROCESS, SWOOLE_SOCK_UNIX_STREAM);

        $server->set(array_merge([
            'worker_num' => $this->workersMax,
            'dispatch_mode' => 3,
        ], SimplePacket::SW_CONN_ARGS));

        $server->on('workerStart', [$this, 'mgrWorkerStart']);
        $server->on('receive', [$this, 'packetReceived']);

        Monitor::init($this->workersMax);
        $server->start();
    }

    /**
     * @param SocketServer $server
     * @param $workerID
     */
    final public function mgrWorkerStart(SocketServer $server, $workerID)
    {
        Monitor::prepare($workerID);
    }

    /**
     * @param SocketServer $server
     * @param $fd
     * @param $fromID
     * @param $data
     */
    final public function packetReceived(SocketServer $server, $fd, $fromID, $data)
    {
        $this->onPacketReceived($server, $fd, $fromID, SimplePacket::unpack($data));
    }

    /**
     * @param SocketServer $server
     * @param $fd
     * @param $fromID
     * @param $data
     */
    abstract protected function onPacketReceived(SocketServer $server, $fd, $fromID, $data);
}