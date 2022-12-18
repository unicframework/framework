<?php

namespace Unic\Router;

class HttpRoute
{
    private $route = [];

    public function __construct(&$method, string &$route, array &$callbacks, array &$routes)
    {
        $this->route = [
            'type' => 'route',
            'callbacks' => $callbacks,
            'route' => [
                'method' => $method,
                'path' => $route,
                'regex' => null,
                'name' => null,
                'params' => [],
            ],
        ];
        $routes[] = &$this->route;
    }

    public function name(string $name)
    {
        $this->route['route']['name'] = $name;
    }
}
