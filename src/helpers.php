<?php

use Unic\App;
use Unic\Config;
use Unic\Http\Request;

ob_start();

if (!function_exists('route')) {
    function route(string $name, array $params = [])
    {
        $path = App::route($name, $params);
        if ($path != null) {
            return url($path);
        }
        return null;
    }
}

if (!function_exists('base_url')) {
    function base_url()
    {
        return Request::getInstance()->baseUrl;
    }
}

if (!function_exists('url')) {
    function url(string $path = '')
    {
        return Request::getInstance()->baseUrl . '/' . trim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = '')
    {
        $staticPath = trim(Config::get('publicUrl'), '/') . '/' . ltrim($path, '/');
        return Request::getInstance()->baseUrl . '/' . ltrim($staticPath, '/');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = '')
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . trim($path, '/');
    }
}
