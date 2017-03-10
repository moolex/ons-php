<?php
/**
 * Authorized info
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:44 PM
 */

namespace ONS\Access;

class Authorized
{
    private $endpoint = 'publictest-rest.ons.aliyun.com';

    /**
     * @var string
     */
    private $topic = '';

    /**
     * @var string
     */
    private $keyID = '';

    /**
     * @var string
     */
    private $keySecret = '';

    /**
     * Authorized constructor.
     * @param $endpoint
     * @param $topic
     * @param $keyID
     * @param $keySecret
     */
    public function __construct($endpoint, $topic, $keyID, $keySecret)
    {
        $this->endpoint = $endpoint;
        $this->topic = $topic;
        $this->keyID = $keyID;
        $this->keySecret = $keySecret;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return string
     */
    public function getKeyID()
    {
        return $this->keyID;
    }

    /**
     * @return string
     */
    public function getKeySecret()
    {
        return $this->keySecret;
    }
}