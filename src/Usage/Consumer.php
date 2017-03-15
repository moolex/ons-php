<?php
/**
 * ONS Consumer
 * User: moyo
 * Date: 15/03/2017
 * Time: 2:05 PM
 */

namespace ONS\Usage;

use ONS\Access\Authorized;
use ONS\Contract\Transfer;
use ONS\Monitor\Monitor;
use ONS\Transfer\HTTP;
use ONS\Transfer\Pool;

class Consumer
{
    /**
     * @var Transfer
     */
    private $transfer = null;

    /**
     * Consumer constructor.
     * @param Authorized $authorized
     * @param $consumerID
     */
    public function __construct(Authorized $authorized, $consumerID)
    {
        $this->transfer = new Pool();
        $this->transfer->setConnInitializer(function () use ($authorized, $consumerID) {

            $client = new HTTP();
            $client->setAuthorized($authorized);
            $client->setConsumerID($consumerID);
            return $client;

        });
        $this->transfer->setConnMax(1);
    }

    /**
     * @param callable $callback
     */
    public function listen(callable $callback)
    {
        Monitor::init(1);
        Monitor::prepare(0);
        $this->transfer->subscribe($callback);
    }
}