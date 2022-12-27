<?php

namespace Unic;

use BadMethodCallException;
use ReflectionFunction;
use Unic\Http\Request;
use Unic\Http\Response;
use Unic\Router\HttpRouterTrait;
use Exception;
use Throwable;

class App
{
    use HttpRouterTrait;

    public function set(string $config, $value, array $options = [])
    {
        Config::set($config, $value, $options);
    }

    public function static(string $url, string $path, array $options = [])
    {
        Config::set('publicDirPath', rtrim(trim($path), '/'));
        $url = trim(trim($url), '/');
        Config::set('publicUrl', empty($url) ? '' : $url . '/');
        return function (Request $req, Response $res, $next) {
            // Render static file
            if (
                Config::get('publicDirPath') !== NULL &&
                Config::get('publicUrl') !== NULL &&
                preg_match('#^' . Config::get('publicUrl') . '(.*)$#', $req->path, $matches) &&
                is_file(Config::get('publicDirPath') . '/' . $matches[1])
            ) {
                $filePath = Config::get('publicDirPath') . '/' . $matches[1];
                $res->file($filePath);
            } else {
                $next();
            }
        };
    }

    private function requestHander()
    {
        $request = Request::getInstance();
        $response = Response::getInstance();

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
                    if (in_array($request->method(), $parsedRoute[$request->path]['route']['method'])) {
                        $request->params = $parsedRoute[$request->path]['route']['params'];
                        $callbacks = $parsedRoute[$request->path]['callbacks'];
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
        $this->runRouteMiddleware($request, $response, $context);

        if ($response instanceof Response) {
            // Send response
            $this->sendResponse($response);
        }
    }

    private function runRouteMiddleware(Request $request, Response $response, array &$context, $error = null)
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
                if (!empty($error) && $parameterCount <= 3) {
                    return $this->runRouteMiddleware($request, $response, $context, $error);
                }
                // Run middleware
                if ($parameterCount == 2) {
                    return $callback($request, $response);
                } else if ($parameterCount == 3) {
                    return $callback($request, $response, function ($error = null) use ($request, $response, $context) {
                        $this->runRouteMiddleware($request, $response, $context, $error);
                    });
                } else {
                    // Skip error middleware if error is not passed
                    if (empty($error)) {
                        return $this->runRouteMiddleware($request, $response, $context, $error);
                    }
                    if ($parameterCount == 4) {
                        return $callback($error, $request, $response, function ($error = null) use ($request, $response, $context) {
                            $this->runRouteMiddleware($request, $response, $context, $error);
                        });
                    } else {
                        return $callback($error, $request, $response, function ($error = null) use ($request, $response, $context) {
                            $this->runRouteMiddleware($request, $response, $context, $error);
                        }, ...array_fill(0, $parameterCount - 4, null));
                    }
                }
            }
        } catch (Throwable $e) {
            // Run error handler middleware
            if (!empty($context['callStack'][$context['index']])) {
                return $this->runRouteMiddleware($request, $response, $context, $e);
            }
            throw $e;
        } catch (Exception $e) {
            // Run error handler middleware
            if (!empty($context['callStack'][$context['index']])) {
                return $this->runRouteMiddleware($request, $response, $context, $e);
            }
            throw $e;
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
        $this->requestHander();
    }
}
