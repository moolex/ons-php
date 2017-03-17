<?php
/**
 * Result :: timeout
 * User: moyo
 * Date: 17/03/2017
 * Time: 4:43 PM
 */

namespace ONS\Wire\Results;

class Timeout
{
    /**
     * @var string
     */
    private $stage = '';

    /**
     * Timeout constructor.
     * @param $stage
     */
    public function __construct($stage)
    {
        $this->stage = $stage;
    }
}