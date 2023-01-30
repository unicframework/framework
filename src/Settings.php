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
            ],
            'trust_proxy' => [
                'value' => false,
                'options' => [],
            ],
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
            $options = [
                'options' => $options,
                'render' => getViewRenderEngine($value, $options),
            ];
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

    public function enable(string $config)
    {
        $this->setConfig($config, true);
    }

    public function disabled(string $config)
    {
        $this->setConfig($config, false);
    }
}

function getViewRenderEngine(string $engine, array $options = [])
{
    $supportedViewEngines = [
        'php' => function ($_self, $_args, $_context) {
            $_self = $_context->config->get('views') . '/' . trim($_self, '/');
            // Set variables of array.
            foreach ($_args as $variable => $value) {
                ${$variable} = $value;
            }
            // Remove private variables
            unset($_context);
            require_once($_self);
        },
        'twig' => function ($_self, $_args, $_context) {
            $loader = new \Twig\Loader\FilesystemLoader($_context->config->get('views'));
            $twig = new \Twig\Environment($loader, $_context->config->getOptions('view_engine')['options'] ?? []);

            echo $twig->render($_self, $_args);
        },
    ];
    return $supportedViewEngines[$engine] ?? $options['render'];
}
