<?php

namespace Unic;

use BadMethodCallException;
use ReflectionFunction;
use Unic\Http\Request;
use Unic\Http\Response;
use Unic\Router\HttpRouterTrait;
use Unic\Middleware\MiddlewareTrait;
use Unic\Server\ServerTrait;
use Exception;
use stdClass;
use Throwable;

class App
{
    use HttpRouterTrait,
        MiddlewareTrait,
        ServerTrait,
        Helpers {
        HttpRouterTrait::get as protected getMethod;
    }

    public $config = null;
    public $locals = null;
    private $context = [];

    public function __construct()
    {
        $this->locals = new stdClass();
        $this->config = new Settings();
    }

    public function set($config, $value = null, array $options = [])
    {
        $this->config->set($config, $value, $options);
    }

    public function get(string $route, ...$callback)
    {
        if (func_num_args() === 1) {
            return $this->config->get($route);
        }
        return $this->getMethod($route, ...$callback);
    }

    private function dispatch(Request &$request, Response &$response)
    {
        $routeNotMatched = true;

        $callStack = [];
        foreach ($this->routes as $row) {
            // Compiler routes
            if ($row['type'] == 'route') {
                if (preg_match_all('/{([^{]*)}/', $row['route']['path'], $matches)) {
                    $skipped = false;
                    foreach ($matches as $match) {
                        // Skip first data from array
                        if ($skipped == false) {
                            $skipped = true;
                        } else {
                            $params = $match;
                        }
                    }
                } else {
                    $params = [];
                }

                $row['route']['params'] = $params;
                $row['route']['regex'] = preg_replace('/{([^{]*)}/', '([^/]+)', $row['route']['path']);

                if (!empty($row['route']['name'])) {
                    self::$namedRoutes[$row['route']['name']] = [
                        'path' => $row['route']['path'],
                        'params' => $row['route']['params'],
                    ];
                }
            }

            // Add callbacks into callstack
            $callbacks = [];
            if ($row['type'] == 'middleware') {
                $callbacks = $row['callbacks'];
            } else {
                $parsedRoute = [];
                // Parse route path parameters
                if (preg_match("#^{$row['route']['regex']}$#", $request->path, $matches)) {
                    $params = array();
                    $i = 1;
                    foreach ($row['route']['params'] as $param) {
                        $params[$param] = $matches[$i];
                        $i++;
                    }
                    $row['route']['params'] = (object) $params;
                    $parsedRoute[$matches[0]] = $row;
                } else {
                    $parsedRoute[$row['route']['path']] = $row;
                }
                if (!empty($parsedRoute[$request->path])) {
                    if (in_array($request->method, $parsedRoute[$request->path]['route']['method'])) {
                        $request->params = $parsedRoute[$request->path]['route']['params'];
                        $callbacks = $parsedRoute[$request->path]['callbacks'];
                        $routeNotMatched = false;
                    }
                }
            }
            // Add callbacks to callstack
            if (!empty($callbacks)) {
                foreach ($callbacks as $callback) {
                    $callStack[] = $callback;
                }
            }
        }

        // Run callstack
        $context = [
            'callStack' => $callStack,
            'index' => 0,
        ];
        $this->runMiddleware($request, $response, $context);

        // Page not found
        if ($routeNotMatched == true && $response instanceof Response && $response->headerSent() === false) {
            $response->send('404 Page Not Found', 404);
            $response->end();
        }
        $request = null;
        $response = null;
    }

    private function runMiddleware(Request $request, Response $response, array &$context, $error = null)
    {
        try {
            if (!empty($context['callStack'][$context['index']])) {
                $callback = $context['callStack'][$context['index']];
                $context['index']++;
                if (!is_callable($callback)) {
                    throw new BadMethodCallException();
                }
                $function = new ReflectionFunction($callback);
                $parameters = $function->getParameters();
                $parameterCount = count($parameters);
                // Skip route middleware if error is passed
                if ($error !== null && $parameterCount <= 3) {
                    return $this->runMiddleware($request, $response, $context, $error);
                }
                // Run middleware
                if ($parameterCount == 2) {
                    return $callback($request, $response);
                } else if ($parameterCount == 3) {
                    return $callback($request, $response, function ($error = null) use ($request, $response, $context) {
                        $this->runMiddleware($request, $response, $context, $error);
                    });
                } else {
                    // Skip error middleware if error is not passed
                    if ($error === null) {
                        return $this->runMiddleware($request, $response, $context, $error);
                    }
                    if ($parameterCount == 4) {
                        return $callback($error, $request, $response, function ($error = null) use ($request, $response, $context) {
                            $this->runMiddleware($request, $response, $context, $error);
                        });
                    } else {
                        return $callback($error, $request, $response, function ($error = null) use ($request, $response, $context) {
                            $this->runMiddleware($request, $response, $context, $error);
                        }, ...array_fill(0, $parameterCount - 4, null));
                    }
                }
            }
        } catch (Throwable $e) {
            // Run error handler middleware
            if (!empty($context['callStack'][$context['index']])) {
                return $this->runMiddleware($request, $response, $context, $e ?? 'Internal Server Error');
            }
            throw $e;
        } catch (Exception $e) {
            // Run error handler middleware
            if (!empty($context['callStack'][$context['index']])) {
                return $this->runMiddleware($request, $response, $context, $e ?? 'Internal Server Error');
            }
            throw $e;
        }
    }

    private function handler(&$request = null, &$response = null)
    {
        if ($this->config->get('server') === 'php') {
            ob_start();
        }
        $this->context['request'] = new Request($request, $this);
        $this->context['response'] = new Response($response, $this);
        $this->dispatch($this->context['request'], $this->context['response']);
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    public function start()
    {
        if ($this->config->get('server') === null) {
            $this->config->set('server', 'php', [
                'server_instance' => null
            ]);
            $this->handler($_SERVER);
        } else if ($this->config->get('server') === 'php') {
            $this->handler($_SERVER);
        } else if ($this->config->get('server') === 'openswoole') {
            $server = $this->config->getOptions('server')['server_instance'] ?? null;
            if ($server === null) {
                throw new Exception('Error: openswoole instance is invalid');
            }
            $server->on("request", function ($request, $response) {
                $this->handler($request, $response);
            });
            $server->start();
        } else {
            throw new Exception('Error: ' . $this->config->get('server') . ' server is not supported');
        }
    }
}
