<?php
/**
 * Deliver signal state
 * User: moyo
 * Date: 17/03/2017
 * Time: 3:34 PM
 */

namespace ONS\Usage\Relays\Deliver;

use ONS\Utils\SimplePacket;

class Signal
{
    /**
     * @param $response
     * @return string
     */
    public static function detect($response)
    {
        $resp = SimplePacket::unpack($response);

        $sig = substr($resp, 0, 3);

        return [$sig, substr($resp, 4)];
    }
}