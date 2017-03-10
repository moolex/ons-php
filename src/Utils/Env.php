<?php
/**
 * Env variable fetch
 * User: moyo
 * Date: 10/03/2017
 * Time: 2:55 PM
 */

namespace ONS\Utils;

class Env
{
    /**
     * @param $name
     * @return string
     */
    public static function get($name)
    {
        $value = getenv($name);

        if (empty($value))
        {
            exit('Env key "'.$name.'" is missing');
        }

        return $value;
    }
}