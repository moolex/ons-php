<?php

require '../vendor/autoload.php';

use ONS\Utils\Env;

$authorized = new \ONS\Access\Authorized(
    Env::get('ONS_ENDPOINT'),
    Env::get('ONS_TOPIC'),
    Env::get('ONS_ACCESS_ID'),
    Env::get('ONS_ACCESS_SECRET')
);

\ONS\Monitor\Monitor::setWebAPI(Env::get('API_LISTEN_PORT', 12334));

$producerID = Env::get('ONS_PRODUCER_ID');

$producer = new \ONS\Usage\Producer($authorized, $producerID, Env::get('CONN_MAX', 1), Env::get('QUEUE_MAX', 1000));

$server = new \ONS\Usage\Relays\UDP(
    $producer,
    Env::get('LISTEN_ADDRESS', '127.0.0.1'),
    Env::get('LISTEN_PORT', 12333),
    Env::get('WORKER_NUM', 1)
);

$server->start();