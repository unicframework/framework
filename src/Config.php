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
            $value = strtolower(trim($value));
        }
        if ($config == 'cache_path') {
            $value = rtrim(trim($value), '/');
            if (!file_exists($value) || !is_file($value)) {
                mkdir($value, 0777, true);
            } else {
                throw new Exception('Invalid cache dir');
            }
        }
        self::$config[$config] = $value;
    }
}
