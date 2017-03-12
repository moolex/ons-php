<?php
/**
 * Monitor :: worker process
 * User: moyo
 * Date: 12/03/2017
 * Time: 9:37 PM
 */

namespace ONS\Monitor\Components;

use ONS\Monitor\Processor;

trait WorkerProcess
{
    /**
     * @var Processor
     */
    private static $ctxInstance = null;

    /**
     * @param $workerID
     */
    private static function prepareProcessor($workerID)
    {
        self::$ctxInstance = new Processor($workerID);
    }

    /**
     * @return Processor
     */
    public static function ctx()
    {
        return self::$ctxInstance;
    }
}