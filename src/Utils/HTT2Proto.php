<?php
/**
 * HTTP protocol helper
 * User: moyo
 * Date: 15/03/2017
 * Time: 4:10 PM
 */

namespace ONS\Utils;

class HTT2Proto
{
    /**
     * @param $raw
     * @return array|null
     */
    public static function parseRequest($raw)
    {
        $firstLine = substr($raw, 0, strpos($raw, "\r\n") - 1);

        $HTTPFlag = substr($firstLine, -8);
        if ($HTTPFlag == 'HTTP/1.1')
        {
            $mpEND = strpos($firstLine, ' ');

            $methodName = substr($firstLine, 0, $mpEND);
            $requestURI = substr($firstLine, $mpEND + 1, -9);

            return [
                'method' => $methodName,
                'uri' => $requestURI,
            ];
        }
        else
        {
            return null;
        }
    }

    /**
     * @param $raw
     * @return array|null
     */
    public static function parseResponse($raw)
    {
        $firstLine = substr($raw, 0, strpos($raw, "\r\n"));

        $HTTPFlag = substr($firstLine, 0, 8);
        if ($HTTPFlag == 'HTTP/1.1')
        {
            $code = substr($firstLine, 9, 3);
            $msg = substr($firstLine, 14);

            $length = 0;
            $lpBGN = strpos($raw, 'Content-Length:');
            if ($lpBGN)
            {
                // C-L: is 15
                $lpBGN += 15;
                // LEN allow 1073741824
                $lpSample = substr($raw, $lpBGN, 12);
                $lpLEN = 12 - strpos($lpSample, "\r\n");
                $length = (int)trim(substr($raw, $lpBGN, $lpLEN));
            }

            $body = null;
            if ($length)
            {
                $plBGN = strpos($raw, "\r\n\r\n");
                $body = substr($raw, $plBGN + 4, $length);
            }

            return [
                'code' => $code,
                'msg' => $msg,
                'body' => $body
            ];
        }
        else
        {
            return null;
        }
    }
}