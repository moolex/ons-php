<?php

require '../vendor/autoload.php';

use ONS\Utils\Env;

$authorized = new \ONS\Access\Authorized(
    Env::get('ONS_ENDPOINT'),
    Env::get('ONS_TOPIC'),
    Env::get('ONS_ACCESS_ID'),
    Env::get('ONS_ACCESS_SECRET')
);

$producerID = Env::get('ONS_PRODUCER_ID');

$transfer = new \ONS\Transfer\Pool();
$transfer->setConnInitializer(function () use ($authorized, $producerID) {

    $client = new \ONS\Transfer\HTTP();
    $client->setAuthorized($authorized);
    $client->setProducerID($producerID);
    return $client;

});
$transfer->setConnMax(Env::get('CONN_MAX', 100));
$transfer->setQueueMax(Env::get('QUEUE_MAX', 10000));

\ONS\Monitor\Monitor::setWebAPI(Env::get('API_LISTEN_PORT', 12334));

$server = new \ONS\Usage\Relays\UDP(
    $transfer,
    Env::get('LISTEN_ADDRESS', '127.0.0.1'),
    Env::get('LISTEN_PORT', 12333),
    Env::get('WORKER_NUM', 4)
);

$server->start();