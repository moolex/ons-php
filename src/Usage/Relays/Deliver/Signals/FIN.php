<?php
/**
 * Deliver msg is FIN
 * User: moyo
 * Date: 17/03/2017
 * Time: 3:25 PM
 */

namespace ONS\Usage\Relays\Deliver\Signals;

use ONS\Utils\SimplePacket;

class FIN
{
    const SYM = 'FIN';

    /**
     * @param $msgHandle
     * @return string
     */
    public static function resp($msgHandle)
    {
        return SimplePacket::pack(self::SYM.':'.$msgHandle);
    }
}