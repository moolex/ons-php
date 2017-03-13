<?php
/**
 * Monitor :: worker stats
 * User: moyo
 * Date: 13/03/2017
 * Time: 10:42 AM
 */

namespace ONS\Monitor\Components;

use ONS\Monitor\Metrics;
use ONS\Monitor\Monitor;

trait WorkerStats
{
    /**
     * @param $workerID
     */
    private static function prepareWorkerMonitor($workerID)
    {
        Monitor::ctx()->registerReporter([__CLASS__, 'workerStatsReportMetrics']);
    }

    /**
     * Worker stats reporter
     */
    public static function workerStatsReportMetrics()
    {
        $psl = exec('ps u -p '.getmypid());

        $cpuDot = strpos($psl, '.');
        $cpuStart = strrpos(substr($psl, 0, $cpuDot), ' ');
        $cpuEnd = $cpuDot + strpos(substr($psl, $cpuDot), ' ');

        $cpu = substr($psl, $cpuStart, $cpuEnd - $cpuStart);
        $memory = memory_get_usage();

        return [
            Metrics::STATS_CPU_USAGE => $cpu,
            Metrics::STATS_MEM_BYTES => $memory,
        ];
    }
}