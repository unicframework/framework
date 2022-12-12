<?php

namespace Unic\Router;

use Exception;
use InvalidArgumentException;
use Unic\Router\HttpRoute;

trait HttpRouterTrait
{
    private $routes = [];
    private static $namedRoutes = [];
    private static $compiledRoutes = [];
    private $supportedMethods = [
        'checkout',
        'copy',
        'delete',
        'get',
        'head',
        'lock',
        'merge',
        'mkactivity',
        'mkcol',
        'move',
        'notify',
        'options',
        'patch',
        'post',
        'purge',
        'put',
        'report',
        'search',
        'subscribe',
        'trace',
        'unlock',
        'unsubscribe',
    ];
    private $prefix = null;

    protected function compile()
    {
        foreach ($this->routes as $row) {
            if ($row['type'] == 'route') {
                if (preg_match_all('/{([^{]*)}/', $row['route']['path'], $matches)) {
                    //Remove first data from array
                    array_shift($matches);
                    foreach ($matches as $match) {
                        $params = $match;
                    }
                } else {
                    $params = array();
                }

                $row['route']['params'] = $params;
                $row['route']['regex'] = preg_replace('/{([^{]*)}/', '([^/]+)', $row['route']['path']);

                self::$compiledRoutes[] = $row;
                if (!empty($row['route']['name'])) {
                    self::$namedRoutes[$row['route']['name']] = [
                        'path' => $row['route']['path'],
                        'params' => $row['route']['params'],
                    ];
                }
            } else {
                self::$compiledRoutes[] = $row;
            }
        }
    }

    protected function getRoute()
    {
        return $this->routes;
    }

    protected function getCompiledRoute()
    {
        return self::$compiledRoutes;
    }

    public static function route(string $name, array $params = [])
    {
        if (isset(self::$namedRoutes[$name])) {
            if (!empty(self::$namedRoutes[$name]['params'])) {
                if (empty($params) || count(self::$namedRoutes[$name]['params']) != count($params)) {
                    throw new InvalidArgumentException('Inavlid route params');
                }
                $path = self::$namedRoutes[$name]['path'];
                foreach ($params as $key => $value) {
                    if (is_numeric($key)) {
                        $path = preg_replace('/{([^{]*)}/', $value, $path, 1);
                    }
                    $path = preg_replace("/\{$key\}/", $value, $path);
                }
                return $path;
            } else {
                return self::$namedRoutes[$name]['path'];
            }
        }
        return null;
    }

    private function setMiddleware($type, array $callbacks)
    {
        $this->routes[] = [
            'type' => $type,
            'callbacks' => $callbacks
        ];
    }

    private function setRoute($method, string $route, array $callbacks)
    {
        if (is_array($method)) {
            foreach ($method as $row) {
                if (in_array($row, $this->supportedMethods) == false) {
                    throw new Exception('Invalid http method');
                }
            }
        } else {
            $method = [$method];
        }
        $route = trim(trim($route), '/');

        if (!empty($this->prefix)) {
            if (empty($route)) {
                $route = $this->prefix;
            } else {
                $route = $this->prefix . '/' . $route;
            }
            return new HttpRoute($method, $route, $callbacks, $this->routes);
        }
        return new HttpRoute($method, $route, $callbacks, $this->routes);
    }

    protected function mergeRoutes(array $routes)
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    protected function mergeGroupedRoutes(string $route, array $routes)
    {
        $prefix = trim(trim($route), '/');
        $groupedRoutes = [];
        foreach ($routes as $row) {
            if (!empty($prefix)) {
                if (!empty($row['route']['path'])) {
                    $row['route']['path'] = $prefix . '/' . $row['route']['path'];
                }
            }
            $groupedRoutes[] = [
                'type' => 'route',
                'callbacks' => $row['route']['callbacks'],
                'route' => [
                    'method' => $row['route']['method'],
                    'path' => $row['route']['path'],
                    'regex' => null,
                    'name' => $row['route']['name'],
                    'params' => [],
                ],
            ];
        }
        $this->routes = array_merge($this->routes, $groupedRoutes);
    }

    public function group(string $route, $callback)
    {
        $this->prefix = trim(trim($route), '/');
        $callback($this);
    }

    public function use(...$arguments)
    {
        $totalArgs = count($arguments);
        if ($totalArgs == 2) {
            if (gettype($arguments[0]) == 'string' && $arguments[1] instanceof HttpRouter) {
                $this->mergeGroupedRoutes($arguments[0], $arguments[1]->routes());
            } else {
                $this->setMiddleware('middleware', $arguments);
            }
        } else {
            if ($arguments[0] instanceof HttpRouter) {
                $this->mergeRoutes($arguments[0]->routes());
            } else {
                $this->setMiddleware('middleware', $arguments);
            }
        }
    }

    public function checkout(string $route, ...$callback)
    {
        return $this->setRoute('checkout', $route, $callback);
    }

    public function copy(string $route, ...$callback)
    {
        return $this->setRoute('copy', $route, $callback);
    }

    public function delete(string $route, ...$callback)
    {
        return $this->setRoute('delete', $route, $callback);
    }

    public function get(string $route, ...$callback)
    {
        return $this->setRoute('get', $route, $callback);
    }

    public function head(string $route, ...$callback)
    {
        return $this->setRoute('head', $route, $callback);
    }

    public function lock(string $route, ...$callback)
    {
        return $this->setRoute('lock', $route, $callback);
    }

    public function merge(string $route, ...$callback)
    {
        return $this->setRoute('merge', $route, $callback);
    }

    public function mkactivity(string $route, ...$callback)
    {
        return $this->setRoute('mkactivity', $route, $callback);
    }

    public function mkcol(string $route, ...$callback)
    {
        return $this->setRoute('mkcol', $route, $callback);
    }

    public function move(string $route, ...$callback)
    {
        return $this->setRoute('move', $route, $callback);
    }

    public function notify(string $route, ...$callback)
    {
        return $this->setRoute('notify', $route, $callback);
    }

    public function options(string $route, ...$callback)
    {
        return $this->setRoute('options', $route, $callback);
    }

    public function patch(string $route, ...$callback)
    {
        return $this->setRoute('patch', $route, $callback);
    }

    public function post(string $route, ...$callback)
    {
        return $this->setRoute('post', $route, $callback);
    }

    public function purge(string $route, ...$callback)
    {
        return $this->setRoute('purge', $route, $callback);
    }

    public function put(string $route, ...$callback)
    {
        return $this->setRoute('put', $route, $callback);
    }

    public function report(string $route, ...$callback)
    {
        return $this->setRoute('report', $route, $callback);
    }

    public function search(string $route, ...$callback)
    {
        return $this->setRoute('search', $route, $callback);
    }

    public function subscribe(string $route, ...$callback)
    {
        return $this->setRoute('subscribe', $route, $callback);
    }

    public function trace(string $route, ...$callback)
    {
        return $this->setRoute('trace', $route, $callback);
    }

    public function unlock(string $route, ...$callback)
    {
        return $this->setRoute('unlock', $route, $callback);
    }

    public function unsubscribe(string $route, ...$callback)
    {
        return $this->setRoute('unsubscribe', $route, $callback);
    }

    public function any(array $methods, string $route, ...$callback)
    {
        return $this->setRoute(array_map(function ($e) {
            return strtolower(trim($e));
        }, $methods), $route, $callback);
    }

    public function all(string $route, ...$callback)
    {
        return $this->setRoute($this->supportedMethods, $route, $callback);
    }
}
