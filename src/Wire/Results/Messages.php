<?php
/**
 * Result :: messages
 * User: moyo
 * Date: 15/03/2017
 * Time: 4:46 PM
 */

namespace ONS\Wire\Results;

class Messages
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * Messages constructor.
     * @param $body
     */
    public function __construct($body)
    {
        $this->messages = (array)json_decode($body, true);
    }

    /**
     * @return array
     */
    public function gets()
    {
        return $this->messages;
    }
}