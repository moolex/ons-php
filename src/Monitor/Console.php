<?php
/**
 * Console for monitor
 * User: moyo
 * Date: 19/03/2017
 * Time: 1:14 AM
 */

namespace ONS\Monitor;

class Console
{
    /**
     * @var array
     */
    private $metricsHosts = [];

    /**
     * @var array
     */
    private $metricsHistory = [];

    /**
     * Console constructor.
     * @param array $metricsHosts
     */
    public function __construct($metricsHosts = [])
    {
        $this->metricsHosts = $metricsHosts;
    }

    /**
     * live view for metrics
     * @param $refresh
     */
    public function liveView($refresh = 1)
    {
        while (true)
        {
            $lines = [];

            foreach ($this->metricsHosts as $metricHost)
            {
                $comment = 'S';
                $cfPos = strpos($metricHost, '#');
                if ($cfPos)
                {
                    $comment = substr($metricHost, $cfPos + 1);
                    $metricHost = substr($metricHost, 0, $cfPos);
                }

                $lines[] = sprintf('[%s](%s)', $metricHost, $comment);
                $lines[] = $this->getTexts($metricHost);
                $lines[] = str_repeat('-', 32);
            }

            $this->print($lines);

            sleep($refresh);
        }
    }

    /**
     * @param $lines
     */
    private function print($lines)
    {
        for ($i = 0; $i < count($lines); $i ++)
        {
            printf("%c[%dA", 27, 0);
            printf("%c[2K\r", 27);
        }
        foreach ($lines as $line)
        {
            echo $line, "\n";
        }
    }

    /**
     * @param $host
     * @return string
     */
    private function getTexts($host)
    {
        $json = $this->request($host, '/metrics');
        $workers = json_decode($json, true);

        $cpu = 0;
        $mem = 0;
        $upOLD = 0;
        $upNEW = 0;

        $statics = [];
        $dynamics = [];

        foreach ($workers as $metrics)
        {
            $cpu += $metrics['stats.cpu.usage'];
            $mem += $metrics['stats.mem.bytes'];
            $upOLD = max($upOLD, $metrics['stats.up.time']);
            $upNEW = min($upNEW, $metrics['stats.up.time']);

            foreach ($metrics as $key => $val)
            {
                if (substr($key, 0, 6) == 'stats.') continue;

                $QPSd = substr($key, 0, 5) == 'pool.' ? false : true;

                if ($QPSd)
                {
                    isset($dynamics[$key]) || $dynamics[$key] = 0;
                    $dynamics[$key] += $val;
                }
                else
                {
                    isset($statics[$key]) || $statics[$key] = 0;
                    $statics[$key] += $val;
                }
            }
        }

        $header = sprintf('Workers(%d)#%s-%s-[%s|%s]#',
            count($workers), $cpu.'%', $this->memToView($mem),
            $this->timeToView($upOLD), $this->timeToView($upNEW)
        );

        $lineS = '';
        foreach ($statics as $key => $val)
        {
            $lineS .= sprintf('%s=%d', $this->shortKeyName($key), $val);
            $lineS .= "\t";
        }

        $lineD = '';
        foreach ($dynamics as $key => $val)
        {
            if (empty($val)) continue;

            $lastRecord = [];
            $lastTime = 0;
            if (isset($this->metricsHistory[$host]['timestamp']))
            {
                $lastRecord = $this->metricsHistory[$host]['records'];
                $lastTime = $this->metricsHistory[$host]['timestamp'];
            }

            $QPS = 0;
            if (isset($lastRecord[$key]))
            {
                $incrementNum = $val - $lastRecord[$key];
                $timeElapsed = time() - $lastTime;
                $QPS = (int)($incrementNum / $timeElapsed);
            }

            $lineD .= sprintf('%s=%d', $this->shortKeyName($key), $val);

            if ($QPS)
            {
                $lineD .= sprintf('(%s)', $QPS.'/s');
            }

            $lineD .= "\t";

            $this->metricsHistory[$host]['records'][$key] = $val;
        }
        $this->metricsHistory[$host]['timestamp'] = time();

        return $header . "\t" . $lineS . $lineD;
    }

    /**
     * @param $host
     * @param $uri
     * @return string
     */
    private function request($host, $uri)
    {
        $opts = ['http' => ['protocol_version' => '1.1']];
        $ctx = stream_context_create($opts);
        return file_get_contents('http://'.$host.$uri, null, $ctx);
    }

    /**
     * @param $key
     * @return string
     */
    private function shortKeyName($key)
    {
        $short = '';
        $splits = explode('.', $key);
        array_walk($splits, function ($word) use (&$short) {
            $short .= strtoupper(substr($word, 0, 1));
        });
        return $short;
    }

    /**
     * @param $bytes
     * @return string
     */
    private function memToView($bytes)
    {
        if ($bytes > 1048576)
        {
            return (int)($bytes / 1048576).'MB';
        }
        else
        {
            return (int)($bytes / 1024).'KB';
        }
    }

    /**
     * @param $seconds
     * @return string
     */
    private function timeToView($seconds)
    {
        if ($seconds < 1)
        {
            return 'N';
        }

        if ($seconds < 3600)
        {
            return (int)($seconds / 60).'m';
        }
        else if ($seconds < 86400)
        {
            return (int)($seconds / 3600).'h';
        }
        else
        {
            return (int)($seconds / 86400).'d';
        }
    }
}