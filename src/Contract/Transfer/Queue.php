<?php
/**
 * Transfer queue API
 * User: moyo
 * Date: 16/03/2017
 * Time: 4:36 PM
 */

namespace ONS\Contract\Transfer;

use ONS\Contract\Message;

interface Queue
{
    /**
     * @return void
     */
    public function prepareWorks();

    /**
     * @param $data
     * @param callable $resultProcessor
     */
    public function publish($data, callable $resultProcessor);

    /**
     * @param callable $messageProcessor
     */
    public function subscribe(callable $messageProcessor);

    /**
     * @param $handle
     * @param callable $resultProcessor
     */
    public function delete($handle, callable $resultProcessor);

    /**
     * @param Message $message
     * @param callable $resultProcessor
     */
    public function forward(Message $message, callable $resultProcessor);
}