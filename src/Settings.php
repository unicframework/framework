<?php

namespace Unic;

use Exception;

class Settings
{
    private $configs = [];

    public function __construct()
    {
        $this->configs = [
            // Default configurations
            'server' => [
                'value' => 'php',
                'options' => [
                    'server_instance' => $_SERVER,
                ],
            ]
        ];
    }

    public function get(string $config)
    {
        return isset($this->configs[strtolower($config)]['value']) ? $this->configs[strtolower($config)]['value'] : null;
    }

    public function getOptions(string $config)
    {
        return isset($this->configs[strtolower($config)]['options']) ? $this->configs[strtolower($config)]['options'] : null;
    }

    public function set($config, $value = null, array $options = [])
    {
        if (is_array($config)) {
            foreach ($config as $config => $value) {
                if (is_numeric($config)) {
                    continue;
                }
                if (is_array($value)) {
                    $this->setConfig($config, ...$value);
                } else {
                    $this->setConfig($config, $value);
                }
            }
        } else {
            $this->setConfig($config, $value, $options);
        }
    }

    private function setConfig(string $config, $value = null, array $options = [])
    {
        // Remove whitespace and special characters
        $config = strtolower($config);
        if ($config === 'views') {
            $value = rtrim(trim($value), '/');
        }
        if ($config === 'view_engine') {
            $value = strtolower(trim($value));
        }
        if ($config === 'cache_dir') {
            $value = rtrim(trim($value), '/');
            if (!is_dir($value)) {
                mkdir($value, 0777, true);
            } else if (is_file($value)) {
                throw new Exception('Invalid configuration: invalid cache dir');
            }
        }

        // Set config value
        $this->configs[$config]['value'] = $value;
        // Set config options
        if (!empty($options)) {
            $this->configs[$config]['options'] = $options;
        }
    }
}
