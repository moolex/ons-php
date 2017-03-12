<?php
/**
 * Monitor processor
 * User: moyo
 * Date: 12/03/2017
 * Time: 9:27 PM
 */

namespace ONS\Monitor;

class Processor
{
    /**
     * @var int
     */
    private $workerID = 0;

    /**
     * @var int
     */
    private $ivSeconds = 5;

    /**
     * @var callable[]
     */
    private $reporters = [];

    /**
     * Processor constructor.
     * @param $workerID
     */
    public function __construct($workerID)
    {
        $this->workerID = $workerID;

        swoole_timer_tick($this->ivSeconds * 1000, [$this, 'startCollects']);
    }

    /**
     * @param callable $reporter
     */
    public function registerReporter(callable $reporter)
    {
        array_push($this->reporters, $reporter);
    }

    /**
     * Collect stats from reporters
     */
    public function startCollects()
    {
        foreach ($this->reporters as $reporter)
        {
            if (is_callable($reporter))
            {
                $kvs = call_user_func($reporter);
                is_array($kvs) && Monitor::tab()->set($this->workerID, $kvs);
            }
        }
    }

    /**
     * @param $name
     * @param int $step
     */
    public function metricIncr($name, $step = 1)
    {
        Monitor::tab()->incr($this->workerID, $name, $step);
    }

    /**
     * @param $name
     * @param int $step
     */
    public function metricDecr($name, $step = 1)
    {
        Monitor::tab()->decr($this->workerID, $name, $step);
    }
}