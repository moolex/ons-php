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
     * @var int
     */
    private $count = 0;

    /**
     * Messages constructor.
     * @param $body
     */
    public function __construct($body)
    {
        $this->messages = (array)json_decode($body, true);
        $this->count = count($this->messages);
    }

    /**
     * @return array
     */
    public function gets()
    {
        return $this->messages;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->count;
    }
}