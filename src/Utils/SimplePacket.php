<?php
/**
 * Simple packet (with length info)
 * User: moyo
 * Date: 17/03/2017
 * Time: 3:10 PM
 */

namespace ONS\Utils;

class SimplePacket
{
    /**
     * conn settings for swoole
     */
    const SW_CONN_ARGS = [
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ];

    /**
     * @param $data
     * @return string
     */
    public static function pack($data)
    {
        $len = strlen($data);

        return pack('N', $len) . $data;
    }

    /**
     * @param $data
     * @return string
     */
    public static function unpack($data)
    {
        return substr($data, 4);
    }
}