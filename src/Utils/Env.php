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
     * @var bool
     */
    private static $cliArgsInit = false;

    /**
     * @param $name
     * @param $default
     * @return string
     */
    public static function get($name, $default = null)
    {
        self::initCLIArgs();

        $value = getenv($name);

        if (empty($value) && is_null($default))
        {
            exit('Env key "'.$name.'" is missing');
        }

        return $value ?: $default;
    }

    /**
     * Merge cli args to env
     */
    private static function initCLIArgs()
    {
        if (self::$cliArgsInit)
        {
            return;
        }

        $args = $_SERVER['argv'];

        for ($i = 1; $i < count($args); $i ++)
        {
            isset($args[$i]) && putenv(ltrim($args[$i], '--'));
        }

        self::$cliArgsInit = true;
    }
}