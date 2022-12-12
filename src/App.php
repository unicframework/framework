<?php

namespace Unic;

use ReflectionFunction;
use Unic\Http\Request;
use Unic\Http\Response;
use Unic\Router\HttpRouterTrait;

class App
{
    use HttpRouterTrait;

    public function set(string $config, $value)
    {
        Config::set($config, $value);
    }

    public function static(string $url, string $path)
    {
        Config::set('publicDirPath', rtrim(trim($path), '/'));
        Config::set('publicUrl', trim(trim($url), '/'));
    }

    private function requestHander(array $compiledRoutes)
    {
        $request = new Request();
        $response = new Response();

        Config::setInstance('request', $request);

        $callStack = [];
        foreach ($compiledRoutes as $row) {
            $callbacks = [];
            if ($row['type'] == 'middleware') {
                $callbacks = $row['callbacks'];
            } else {
                $requestPath = $request->path;
                // Render static files
                if (
                    Config::get('publicDirPath') !== NULL &&
                    Config::get('publicUrl') !== NULL &&
                    preg_match('#^' . Config::get('publicUrl') . '/(.*)$#', $requestPath, $matches) &&
                    file_exists(Config::get('publicDirPath') . '/' . $matches[1])
                ) {
                    $filePath = Config::get('publicDirPath') . '/' . $matches[1];
                    $callbacks[] = function (Request $request, Response $response) use ($filePath) {
                        $response->file($filePath);
                    };
                } else {
                    $parsedRoute = [];
                    // Parse routes
                    if (preg_match("#^{$row['route']['regex']}$#", $requestPath, $matches)) {
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
                    // Render routes
                    if (!empty($parsedRoute[$requestPath])) {
                        if (in_array(strtolower($request->method), $parsedRoute[$requestPath]['route']['method'])) {
                            $request->params = $parsedRoute[$requestPath]['route']['params'];
                            $callbacks = $parsedRoute[$requestPath]['callbacks'];
                        }
                    }
                }
            }
            if (!empty($callbacks)) {
                $callStack = array_merge($callStack, $callbacks);
            }
        }

        // Run callstack
        $this->runRouteMiddleware($request, $response, $callStack);

        if ($response instanceof Response) {
            // Send response
            $this->sendResponse($response);
        }
    }

    private function runRouteMiddleware(Request $request, Response $response, array &$callbacks, $error = null)
    {
        if (!empty($callbacks)) {
            $callback = $callbacks[0];
            array_shift($callbacks);
            $function = new ReflectionFunction($callback);
            $parameters = $function->getParameters();
            $parameterCount = count($parameters);
            // Skip route middleware if error is passed
            if (!empty($error) && $parameterCount <= 3) {
                return $this->runRouteMiddleware($request, $response, $callbacks, $error);
            }
            // Run middleware
            if ($parameterCount == 2) {
                return $callback($request, $response);
            } else if ($parameterCount == 3) {
                return $callback($request, $response, function ($error = null) use ($request, $response, $callbacks) {
                    $this->runRouteMiddleware($request, $response, $callbacks, $error);
                });
            } else {
                // Skip error middleware if error is not passed
                if (empty($error)) {
                    return $this->runRouteMiddleware($request, $response, $callbacks, $error);
                }
                if ($parameterCount == 4) {
                    return $callback($error, $request, $response, function ($error = null) use ($request, $response, $callbacks) {
                        $this->runRouteMiddleware($request, $response, $callbacks, $error);
                    });
                } else {
                    return $callback($error, $request, $response, function ($error = null) use ($request, $response, $callbacks) {
                        $this->runRouteMiddleware($request, $response, $callbacks, $error);
                    }, ...array_fill(0, $parameterCount - 4, null));
                }
            }
        }
    }

    private function sendResponse(Response $response)
    {
        if ($response->headerIsSent()) {
            return $response->end();
        } else {
            $response->send('404 Page Not Found', 404);
            return $response->end();
        }
    }

    public function start()
    {
        // Compile routes
        $this->compile();
        $this->requestHander($this->getCompiledRoute(), $this->getRoute());
    }
}
