<?php

require 'src/autoload.php';

use ONS\Utils\Env;

$authorized = new \ONS\Access\Authorized(
    Env::get('ONS_ENDPOINT'),
    Env::get('ONS_TOPIC'),
    Env::get('ONS_ACCESS_ID'),
    Env::get('ONS_ACCESS_SECRET')
);

$producerID = Env::get('ONS_PRODUCER_ID');

$transfer = new \ONS\Transfer\ConnPooled();
$transfer->setConnInitializer(function () use ($authorized, $producerID) {

    $client = new \ONS\Transfer\AsyncHTTP();
    $client->setAuthorized($authorized);
    $client->setProducerID($producerID);
    return $client;

});
$transfer->setConnMax(Env::get('CONN_MAX', 100));
$transfer->setQueueMax(Env::get('QUEUE_MAX', 10000));

$server = new \ONS\Relays\UDP(
    Env::get('LISTEN_ADDRESS', '127.0.0.1'),
    Env::get('LISTEN_PORT', 12333),
    $transfer
);

$server->start();