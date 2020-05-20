<?php


namespace Mushroom\Core\Database;


trait ConfigTrait
{
    protected static $conf = [];
    public static function setConfig($config)
    {
        self::$conf = $config;
    }
}