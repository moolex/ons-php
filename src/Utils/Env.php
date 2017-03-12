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
     * @param $default
     * @return string
     */
    public static function get($name, $default = null)
    {
        $value = getenv($name);

        if (empty($value) && is_null($default))
        {
            exit('Env key "'.$name.'" is missing');
        }

        return $value ?: $default;
    }
}