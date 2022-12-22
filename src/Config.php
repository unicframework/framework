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
        // Trim special characters
        if ($config == 'views') {
            $value = rtrim(trim($value), '/');
        }
        if ($config == 'view_engine') {
          $value = trim($value);
        }
        self::$config[$config] = $value;
    }
}
