<?php

require '../vendor/autoload.php';

use ONS\Utils\Env;

$authorized = new \ONS\Access\Authorized(
    Env::get('ONS_ENDPOINT'),
    Env::get('ONS_TOPIC'),
    Env::get('ONS_ACCESS_ID'),
    Env::get('ONS_ACCESS_SECRET')
);

\ONS\Monitor\Monitor::setWebAPI(Env::get('API_LISTEN_PORT', 12335));

$consumerID = Env::get('ONS_CONSUMER_ID');

$consumer = new \ONS\Usage\Consumer($authorized, $consumerID);

$deliver = new \ONS\Usage\Relays\Deliver\Client($consumer, Env::get('UNIX_DOMAIN', '/tmp/deliver.sock'));

$consumer->listen(function (\ONS\Contract\Message $message) use ($deliver) {
    $deliver->forward($message);
});