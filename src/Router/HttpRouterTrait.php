<?php

namespace Unic\Router;

use Exception;
use InvalidArgumentException;
use Unic\Router\HttpRoute;

trait HttpRouterTrait
{
    private $routes = [];
    private static $namedRoutes = [];
    private $supportedMethods = [
        'CHECKOUT',
        'COPY',
        'DELETE',
        'GET',
        'HEAD',
        'LOCK',
        'MERGE',
        'MKACTIVITY',
        'MKCOL',
        'MOVE',
        'NOTIFY',
        'OPTIONS',
        'PATCH',
        'POST',
        'PROPFIND',
        'PURGE',
        'PUT',
        'REPORT',
        'SEARCH',
        'SUBSCRIBE',
        'TRACE',
        'UNLOCK',
        'UNSUBSCRIBE',
        'VIEW',
    ];
    private $prefix = null;

    private function getNamedRoute(string $name, array $params = [])
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
                    } else {
                        $path = preg_replace("/\{$key\}/", $value, $path);
                    }
                }
                return $path;
            } else {
                return !empty(self::$namedRoutes[$name]['path']) ? self::$namedRoutes[$name]['path'] : '/';
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
                    throw new Exception('Error: ' . $row . ' invalid http method');
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
        foreach ($routes as $row) {
            $this->routes[] = $row;
        }
    }

    protected function mergeGroupedRoutes(string $route, array $routes)
    {
        $prefix = trim(trim($route), '/');
        foreach ($routes as $row) {
            if (!empty($prefix)) {
                if (!empty($row['route']['path'])) {
                    $row['route']['path'] = $prefix . '/' . $row['route']['path'];
                } else {
                    $row['route']['path'] = $prefix;
                }
            }
            $this->routes[] = [
                'type' => 'route',
                'callbacks' => $row['callbacks'],
                'route' => [
                    'method' => $row['route']['method'],
                    'path' => $row['route']['path'],
                    'regex' => null,
                    'name' => $row['route']['name'],
                    'params' => [],
                ],
            ];
        }
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
            if (gettype($arguments[0]) === 'string' && $arguments[1] instanceof HttpRouter) {
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
        return $this->setRoute('CHECKOUT', $route, $callback);
    }

    public function copy(string $route, ...$callback)
    {
        return $this->setRoute('COPY', $route, $callback);
    }

    public function delete(string $route, ...$callback)
    {
        return $this->setRoute('DELETE', $route, $callback);
    }

    public function get(string $route, ...$callback)
    {
        return $this->setRoute('GET', $route, $callback);
    }

    public function head(string $route, ...$callback)
    {
        return $this->setRoute('HEAD', $route, $callback);
    }

    public function lock(string $route, ...$callback)
    {
        return $this->setRoute('LOCK', $route, $callback);
    }

    public function merge(string $route, ...$callback)
    {
        return $this->setRoute('MERGE', $route, $callback);
    }

    public function mkactivity(string $route, ...$callback)
    {
        return $this->setRoute('MKACTIVITY', $route, $callback);
    }

    public function mkcol(string $route, ...$callback)
    {
        return $this->setRoute('MKCOL', $route, $callback);
    }

    public function move(string $route, ...$callback)
    {
        return $this->setRoute('MOVE', $route, $callback);
    }

    public function notify(string $route, ...$callback)
    {
        return $this->setRoute('NOTIFY', $route, $callback);
    }

    public function options(string $route, ...$callback)
    {
        return $this->setRoute('OPTIONS', $route, $callback);
    }

    public function patch(string $route, ...$callback)
    {
        return $this->setRoute('PATCH', $route, $callback);
    }

    public function post(string $route, ...$callback)
    {
        return $this->setRoute('POST', $route, $callback);
    }

    public function propfind(string $route, ...$callback)
    {
        return $this->setRoute('PROPFIND', $route, $callback);
    }

    public function purge(string $route, ...$callback)
    {
        return $this->setRoute('PURGE', $route, $callback);
    }

    public function put(string $route, ...$callback)
    {
        return $this->setRoute('PUT', $route, $callback);
    }

    public function report(string $route, ...$callback)
    {
        return $this->setRoute('REPORT', $route, $callback);
    }

    public function search(string $route, ...$callback)
    {
        return $this->setRoute('SEARCH', $route, $callback);
    }

    public function subscribe(string $route, ...$callback)
    {
        return $this->setRoute('SUBSCRIBE', $route, $callback);
    }

    public function trace(string $route, ...$callback)
    {
        return $this->setRoute('TRACE', $route, $callback);
    }

    public function unlock(string $route, ...$callback)
    {
        return $this->setRoute('UNLOCK', $route, $callback);
    }

    public function unsubscribe(string $route, ...$callback)
    {
        return $this->setRoute('UNSUBSCRIBE', $route, $callback);
    }

    public function view(string $route, ...$callback)
    {
        return $this->setRoute('VIEW', $route, $callback);
    }

    public function any(array $methods, string $route, ...$callback)
    {
        return $this->setRoute(array_map(function ($e) {
            return strtoupper(trim($e));
        }, $methods), $route, $callback);
    }

    public function all(string $route, ...$callback)
    {
        return $this->setRoute($this->supportedMethods, $route, $callback);
    }
}
