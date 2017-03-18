<?php
/**
 * Deliver client (send)
 * User: moyo
 * Date: 16/03/2017
 * Time: 3:09 PM
 */

namespace ONS\Usage\Relays\Deliver;

use ONS\Contract\Message;
use ONS\Transfer\Pool;
use ONS\Transfer\UDSocket;
use ONS\Usage\Consumer;
use ONS\Usage\Relays\Deliver\Signals\FIN;

class Client
{
    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * @var Consumer
     */
    private $consumer = null;

    /**
     * Client constructor.
     * @param Consumer $consumer
     * @param $uds
     */
    public function __construct(Consumer $consumer, $uds)
    {
        $this->consumer = $consumer;

        $this->pool = new Pool();
        $this->pool->setConnInitializer(function () use ($uds) {

            return new UDSocket($uds);

        });
    }

    /**
     * @param Message $message
     */
    public function forward(Message $message)
    {
        $this->pool->forward($message, function ($response) {

            list($sig, $key) = Signal::detect($response);

            switch ($sig)
            {
                case FIN::SYM:
                    $this->consumer->delete($key, function ($result) {
                        // do nothing
                    });
                    break;
                default:
                    // do nothing
            }

        });
    }
}