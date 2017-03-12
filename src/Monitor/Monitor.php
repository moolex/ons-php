<?php
/**
 * Monitor API
 * User: moyo
 * Date: 12/03/2017
 * Time: 6:55 PM
 */

namespace ONS\Monitor;

use ONS\Monitor\Components\TableStore;
use ONS\Monitor\Components\WebAPI;
use ONS\Monitor\Components\WorkerProcess;

class Monitor
{
    use TableStore;
    use WorkerProcess;
    use WebAPI;

    /**
     * @param $workersNum
     */
    public static function init($workersNum)
    {
        self::initTableStore($workersNum);
    }

    /**
     * @param $workerID
     */
    public static function prepare($workerID)
    {
        self::prepareProcessor($workerID);
        self::prepareWebAPI($workerID);
    }
}