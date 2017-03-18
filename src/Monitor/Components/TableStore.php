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
        Metrics::NET_PACKET_SEND,
        Metrics::NET_PACKET_RECV,
        Metrics::NET_TIMEOUT_CONNECT,
        Metrics::NET_TIMEOUT_SEND,
        Metrics::NET_CONN_RECONNECT,
        Metrics::ONS_MSG_FETCHED,
        Metrics::ONS_MSG_CREATED,
        Metrics::ONS_MSG_DELETED,
        Metrics::ONS_REQ_FAILED,
        Metrics::ONS_REQ_DENIED,
        Metrics::ONS_TIMEOUT_SERV,
        Metrics::ONS_TIMEOUT_GATE,
        Metrics::STATS_UP_TIME,
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