<?php
/**
 * Connection pool manager
 * User: moyo
 * Date: 11/03/2017
 * Time: 1:31 AM
 */

namespace ONS\Transfer;

use ONS\Contract\Message;
use ONS\Contract\Transfer\Queue;
use ONS\Monitor\Metrics;
use ONS\Monitor\Monitor;

class Pool implements Queue
{
    /**
     * @var int
     */
    private $connMax = 1;

    /**
     * @var callable
     */
    private $connInitializer = null;

    /**
     * @var int
     */
    private $queueMax = 1000;

    /**
     * @var array
     */
    private $queueStack = [];

    /**
     * @var int
     */
    private $sendDrops = 0;

    /**
     * @var array
     */
    private $listIdle = [];

    /**
     * @var int
     */
    private $countBusy = 0;

    /**
     * @param $size
     */
    public function setConnMax($size)
    {
        $this->connMax = $size;
    }

    /**
     * @param callable $creator
     */
    public function setConnInitializer(callable $creator)
    {
        $this->connInitializer = $creator;
    }

    /**
     * @param $size
     */
    public function setQueueMax($size)
    {
        $this->queueMax = $size;
    }

    /**
     * Prepare some things before work
     */
    public function prepareWorks()
    {
        Monitor::ctx()->registerReporter([$this, 'reportMetrics']);
    }

    /**
     * Report self stats
     */
    public function reportMetrics()
    {
        return [
            Metrics::POOL_CONN_IDLE => count($this->listIdle),
            Metrics::POOL_CONN_BUSY => $this->countBusy,
            Metrics::POOL_QUEUE_SIZE => count($this->queueStack),
            Metrics::POOL_QUEUE_DROPS => $this->sendDrops,
        ];
    }

    /**
     * @param $data
     * @param callable $resultProcessor
     */
    public function publish($data, callable $resultProcessor)
    {
        $this->commonWrite('publish', $data, $resultProcessor);
    }

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor)
    {
        while (null != $transfer = $this->getIdleConn())
        {
            $this->setConnBusy();
            $transfer->subscribe($messageProcessor);
        }
    }

    /**
     * @param $handle
     * @param callable $resultProcessor
     */
    public function delete($handle, callable $resultProcessor)
    {
        $this->commonWrite('delete', $handle, $resultProcessor);
    }

    /**
     * @param Message $message
     * @param callable $resultProcessor
     */
    public function forward(Message $message, callable $resultProcessor)
    {
        $this->commonWrite('forward', $message, $resultProcessor);
    }

    /**
     * @param $method
     * @param $payload
     * @param callable $resultProcessor
     */
    private function commonWrite($method, $payload, callable $resultProcessor)
    {
        $transfer = $this->getIdleConn();
        if ($transfer)
        {
            $this->setConnBusy();
            $transfer->$method($payload, function () use ($transfer, $resultProcessor) {
                $this->setIdleConn($transfer);
                call_user_func_array($resultProcessor, func_get_args());
                $this->queueChecks();
            });
        }
        else
        {
            if ($this->queueIsFull())
            {
                $this->sendDrops ++;
                call_user_func_array($resultProcessor, [null]);
            }
            else
            {
                $this->queueAppend($method, $payload, $resultProcessor);
            }
        }
    }

    /**
     * Fetch out conn from idle pool
     * @return Queue
     */
    private function getIdleConn()
    {
        $conn = array_pop($this->listIdle);
        if ($conn)
        {
            return $conn;
        }
        else
        {
            if ($this->countBusy < $this->connMax)
            {
                $newConn = call_user_func($this->connInitializer);
                if ($newConn instanceof Queue)
                {
                    $newConn->prepareWorks();
                }
                return $newConn;
            }
            else
            {
                return null;
            }
        }
    }

    /**
     * Put back conn to idle pool
     * @param Queue $conn
     */
    private function setIdleConn(Queue $conn)
    {
        array_unshift($this->listIdle, $conn);
        $this->countBusy --;
    }

    /**
     * Increment busy conn count
     */
    private function setConnBusy()
    {
        $this->countBusy ++;
    }

    /**
     * @return bool
     */
    private function queueIsFull()
    {
        return count($this->queueStack) >= $this->queueMax;
    }

    /**
     * @param $method
     * @param $payload
     * @param $callback
     */
    private function queueAppend($method, $payload, $callback)
    {
        array_push($this->queueStack, [$method, $payload, $callback]);
    }

    /**
     * Check queue and send to async
     */
    private function queueChecks()
    {
        if ($this->queueStack)
        {
            list($method, $payload, $callback) = array_shift($this->queueStack);
            if (is_callable($callback))
            {
                $this->$method($payload, $callback);
            }
        }
    }
}