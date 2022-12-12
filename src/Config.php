<?php

namespace Unic;

class Config
{
    // Contains all singleton objects
    private static $instanceVariables = [
        'request',
    ];
    private static $instance = [];
    private static $config = [];
    private static $configVariables = [
        'env',
        'views',
        'public',
    ];

    public static function getInstance(string $name)
    {
        return self::$instance[strtolower($name)] ?? null;
    }

    public static function setInstance(string $name, object $value)
    {
        self::$instance[strtolower($name)] = $value;
    }

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
