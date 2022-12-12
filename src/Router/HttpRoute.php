<?php

namespace Unic\Router;

class HttpRoute
{
    private $route = [];
    private $routeData = [];

    public function __construct($method, string &$route, array &$callbacks, array &$routes)
    {
        $this->route = &$route;
        $routes = &$routes;
        $this->routeData = [
            'type' => 'route',
            'callbacks' => $callbacks,
            'route' => [
                'method' => $method,
                'path' => $this->route,
                'regex' => null,
                'name' => null,
                'params' => [],
            ],
        ];
        $routes[] = &$this->routeData;
    }

    public function name(string $name)
    {
        $this->routeData['route']['name'] = $name;
    }
}
