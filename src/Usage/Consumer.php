<?php
/**
 * ONS Consumer
 * User: moyo
 * Date: 15/03/2017
 * Time: 2:05 PM
 */

namespace ONS\Usage;

use ONS\Access\Authorized;
use ONS\Monitor\Monitor;
use ONS\Transfer\HTTP;
use ONS\Transfer\Pool;

class Consumer
{
    /**
     * @var Pool
     */
    private $poolSUB = null;

    /**
     * @var Pool
     */
    private $poolDEL = null;

    /**
     * Consumer constructor.
     * @param Authorized $authorized
     * @param $consumerID
     * @param int $connMax
     */
    public function __construct(Authorized $authorized, $consumerID, $connMax = 1)
    {
        $initializer = function () use ($authorized, $consumerID) {

            $client = new HTTP();
            $client->setAuthorized($authorized);
            $client->setConsumerID($consumerID);
            return $client;

        };

        $this->poolSUB = new Pool();
        $this->poolSUB->setConnInitializer($initializer);
        $this->poolSUB->setConnMax($connMax);

        $this->poolDEL = new Pool();
        $this->poolDEL->setConnInitializer($initializer);
        $this->poolDEL->setConnMax($connMax);
    }

    /**
     * @param callable $messageProcessor
     */
    public function listen(callable $messageProcessor)
    {
        Monitor::init(1);
        Monitor::prepare(0);
        $this->poolDEL->prepareWorks();

        $this->poolSUB->subscribe($messageProcessor);
    }

    /**
     * @param $handle
     * @param callable $resultProcessor
     */
    public function delete($handle, callable $resultProcessor)
    {
        $this->poolDEL->delete($handle, $resultProcessor);
    }
}