<?php

require '../vendor/autoload.php';

use ONS\Utils\Env;

class UnixServerTest extends \ONS\Usage\Relays\Deliver\Server
{
    protected function onPacketReceived(\swoole_server $server, $fd, $fromID, $data)
    {
        $message = \ONS\Wire\Message::serializeUnpack($data);

        echo 'GOT ', $message->getID(), ' : ATS ', $message->getAttempts(), "\n";

        $server->send($fd, \ONS\Usage\Relays\Deliver\Signals\FIN::resp($message->getHandle()));
    }
}

\ONS\Monitor\Monitor::setWebAPI(Env::get('API_LISTEN_PORT', 12336), Env::get('API_LISTEN_HOST', '127.0.0.1'));

$server = new UnixServerTest();
$server->setUnixSock(Env::get('UNIX_DOMAIN', '/tmp/deliver.sock'));
$server->start();