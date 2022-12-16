<?php

namespace Unic;

class Config
{
    private static $config = [];
    public static function get(string $config)
    {
        $config = strtolower($config);
        return self::$config[$config] ?? null;
    }

    public static function set(string $config, $value)
    {
        $config = strtolower($config);
        if (in_array($config, ['views', 'public'])) {
            $value = rtrim($value, '/');
        }
        self::$config[$config] = trim($value);
    }
}
