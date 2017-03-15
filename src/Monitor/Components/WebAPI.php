<?php
/**
 * Monitor :: web api
 * User: moyo
 * Date: 12/03/2017
 * Time: 9:35 PM
 */

namespace ONS\Monitor\Components;

use ONS\Monitor\Monitor;
use ONS\Utils\HTT2Proto;

trait WebAPI
{
    /**
     * @var string
     */
    private static $webAPIHost = '127.0.0.1';

    /**
     * @var int
     */
    private static $webAPIPort = 0;

    /**
     * @param $port
     * @param string $host
     */
    public static function setWebAPI($port, $host = '127.0.0.1')
    {
        self::$webAPIHost = $host;
        self::$webAPIPort = $port;
    }

    /**
     * @param $workerID
     */
    private static function prepareWebAPI($workerID)
    {
        if ($workerID == 0 && self::$webAPIPort)
        {
            self::startWebAPI();
        }
    }

    /**
     * Start local API server
     */
    private static function startWebAPI()
    {
        $errNum = 0;
        $errStr = '';

        $listener = stream_socket_server(sprintf('tcp://%s:%d', self::$webAPIHost, self::$webAPIPort), $errNum, $errStr);
        if ($listener)
        {
            swoole_event_add($listener, function($server) {
                $accepted = stream_socket_accept($server, 0);
                swoole_event_add($accepted, function($sock) {
                    $headers = fread($sock, 8192);
                    if (null != $request = HTT2Proto::parseRequest($headers))
                    {
                        switch ($request['method'].'-'.$request['uri'])
                        {
                            case 'GET-/':
                                self::sendHTTPResponse($sock, 'API WORKS');
                                break;
                            case 'GET-/metrics':
                                self::sendHTTPResponse($sock, self::genJsonMetrics(), 'application/json');
                                break;
                            default:
                                self::sendHTTPResponse($sock, 'REQUEST NOT FOUND', 'text/html', 404);
                        }
                    }
                    fclose($sock);
                });
            });
        }
    }

    /**
     * @return string
     */
    private static function genJsonMetrics()
    {
        $dat = [];

        foreach (Monitor::tab() as $workerID => $workerStats)
        {
            $dat[$workerID] = $workerStats;
        }

        return json_encode($dat);
    }

    /**
     * @param resource $sock
     * @param $data
     * @param string $type
     * @param int $code
     */
    private static function sendHTTPResponse($sock, $data, $type = 'text/html', $code = 200)
    {
        $size = strlen($data);

        $buffer =
            "HTTP/1.1 {$code}\r\n".
            "Server: ONS-PHP Monitor\r\n".
            "Content-Type: {$type}\r\n".
            "Content-Length: {$size}\r\n".
            "Connection: close\r\n".
            "\r\n".
            $data
        ;

        fwrite($sock, $buffer);
    }
}