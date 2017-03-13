<?php
/**
 * Monitor :: metrics :: table store
 * User: moyo
 * Date: 12/03/2017
 * Time: 9:31 PM
 */

namespace ONS\Monitor\Components;

use ONS\Monitor\Metrics;
use swoole_table as Table;

trait TableStore
{
    /**
     * @var Table
     */
    private static $tabInstance = null;

    /**
     * @var array
     */
    private static $knownMetrics = [
        Metrics::POOL_CONN_IDLE,
        Metrics::POOL_CONN_BUSY,
        Metrics::POOL_QUEUE_SIZE,
        Metrics::POOL_QUEUE_DROPS,
        Metrics::MSG_FORWARD_SUBMIT,
        Metrics::MSG_FORWARD_RESPONSE,
        Metrics::MSG_FORWARD_TIMEOUT,
        Metrics::NET_CONNECT_TIMEOUT,
        Metrics::STATS_CPU_USAGE,
        Metrics::STATS_MEM_BYTES,
    ];

    /**
     * @param $workersNum
     */
    private static function initTableStore($workersNum)
    {
        self::$tabInstance = new Table($workersNum);
        foreach (self::$knownMetrics as $metricKey)
        {
            self::$tabInstance->column($metricKey, Table::TYPE_INT);
        }
        self::$tabInstance->create();
    }

    /**
     * @return Table
     */
    public static function tab()
    {
        return self::$tabInstance;
    }
}