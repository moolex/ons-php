<?php

require '../vendor/autoload.php';

use ONS\Utils\Env;

class UnixServerTest extends \ONS\Usage\Relays\Deliver\Server
{
    protected function onConnEstablished(\swoole_server $server, $fd)
    {
        echo 'conn established from ', $fd, "\n";
    }

    protected function onPacketReceived(\swoole_server $server, $fd, $fromID, $data)
    {
        $message = \ONS\Wire\Message::serializeUnpack($data);

        echo 'got msg id is ', $message->getID(), "\n";

        $server->send($fd, \ONS\Usage\Relays\Deliver\Signals\FIN::resp($message->getHandle()));
    }
}

$server = new UnixServerTest();
$server->setUnixSock(Env::get('UNIX_DOMAIN', '/tmp/deliver.sock'));
$server->start();