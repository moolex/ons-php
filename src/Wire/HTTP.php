<?php
/**
 * HTTP defines
 * User: moyo
 * Date: 15/03/2017
 * Time: 2:34 PM
 */

namespace ONS\Wire;

use ONS\Access\Authorized;
use ONS\Utils\HTT2Proto;

class HTTP
{
    /**
     * @var Authorized
     */
    private $authorized = null;

    /**
     * @var string
     */
    private $userAgent = '';

    /**
     * @var string
     */
    private $tag = 'http';

    /**
     * @var string
     */
    private $key = 'http';

    /**
     * HTTP constructor.
     * @param Authorized $authorized
     * @param $userAgent
     */
    public function __construct(Authorized $authorized, $userAgent = 'ons-php')
    {
        $this->authorized = $authorized;
        $this->userAgent = $userAgent;
    }

    /**
     * @param $producerID
     * @param $payload
     * @param $later
     * @return string
     */
    public function publish($producerID, $payload, $later = null)
    {
        $date = $this->genMSTime();

        $hash = md5($payload);
        $sample = "{$this->authorized->getTopic()}\n{$producerID}\n{$hash}\n{$date}";

        $args = ['time' => $date];

        $later && $args['startdelivertime'] = $later;

        return $this->genHTTP(
            'POST',
            $args,
            $this->genSign($sample),
            null,
            $producerID,
            $payload
        );
    }

    /**
     * @param $consumerID
     * @return string
     */
    public function subscribe($consumerID)
    {
        $date = $this->genMSTime();

        $sample = "{$this->authorized->getTopic()}\n{$consumerID}\n{$date}";

        return $this->genHTTP(
            'GET',
            ['time' => $date],
            $this->genSign($sample),
            $consumerID
        );
    }

    /**
     * @param $consumerID
     * @param $handle
     * @return string
     */
    public function delete($consumerID, $handle)
    {
        $date = $this->genMSTime();

        $sample = "{$this->authorized->getTopic()}\n{$consumerID}\n{$handle}\n{$date}";

        return $this->genHTTP(
            'DELETE',
            ['time' => $date, 'msghandle' => $handle],
            $this->genSign($sample),
            $consumerID
        );
    }

    /**
     * @param $raw
     * @return Results\Messages | bool | null
     */
    public function result($raw)
    {
        $parsed = HTT2Proto::parseResponse($raw);
        if (is_array($parsed) && isset($parsed['code']))
        {
            switch ($parsed['code'])
            {
                case 200:
                    return new Results\Messages($parsed['body']);
                    break;
                case 201:
                    return true;
                    break;
                case 204:
                    return true;
                    break;
            }
        }
        return null;
    }

    /**
     * @return int
     */
    private function genMSTime()
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * @param $sample
     * @return string
     */
    private function genSign($sample)
    {
        return $sign = base64_encode(hash_hmac('sha1', $sample, $this->authorized->getKeySecret(), true));
    }

    /**
     * @param $method
     * @param $exArgs
     * @param $sign
     * @param null $consumerID
     * @param null $producerID
     * @param null $body
     * @return string
     */
    private function genHTTP($method, $exArgs, $sign, $consumerID = null, $producerID = null, $body = null)
    {
        $uriArgs = [
            'topic' => $this->authorized->getTopic(),
            'tag' => $this->tag,
            'key' => $this->key,
        ];

        if  ($exArgs)
        {
            $uriArgs = array_merge($uriArgs, $exArgs);
        }

        $uri = http_build_query($uriArgs);

        $text =
            "{$method} /message/?{$uri} HTTP/1.1\r\n".
            "Host: {$this->authorized->getEndpoint()}\r\n".
            "Connection: keep-alive\r\n".
            "User-Agent: {$this->userAgent}\r\n".
            "AccessKey: {$this->authorized->getKeyID()}\r\n".
            "Signature: {$sign}\r\n"
        ;

        if ($method == 'POST')
        {
            $size = strlen($body);
            $text .=
                "ProducerID: {$producerID}\r\n".
                "Content-Type: text/html;charset=UTF-8\r\n".
                "Content-Length: {$size}\r\n".
                "\r\n".
                "{$body}"
            ;
        }
        else
        {
            $text .=
                "ConsumerID: {$consumerID}".
                "\r\n\r\n"
            ;
        }

        return $text;
    }
}