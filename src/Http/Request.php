<?php

namespace Unic\Http;

use Unic\Http\Request\IRequest;
use Unic\Http\Request\PHPRequest;
use Exception;
use Unic\Http\Request\OpenSwooleRequest;

class Request implements IRequest
{
    // Request info
    public $host = null;
    public $hostname = null;
    public $port = null;
    public $method = null;
    public $protocol = null;
    public $url = null;
    public $path = null;
    public $queryString = null;
    public $params = null;

    public $app = null;
    private $request = null;

    public function __construct(&$request, &$app)
    {
        $this->app = $app;

        if ($this->app->config->get('server') === 'php') {
            $this->request = new PHPRequest($request, $this->app);
        } else if ($this->app->config->get('server') === 'openswoole') {
            $this->request = new OpenSwooleRequest($request, $this->app);
        } else {
            throw new Exception('Error: ' . $this->app->config->get('server') . ' server is not supported');
        }

        // Set properties of request
        foreach ($this->request as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function scheme(string $header = null)
    {
        return $this->request->scheme($header);
    }

    public function header(string $header = null)
    {
        return $this->request->header($header);
    }

    public function rawHeader()
    {
        return $this->request->rawHeader();
    }

    public function body(string $key = null)
    {
        return $this->request->body($key);
    }

    public function rawBody()
    {
        return $this->request->rawBody();
    }

    public function query(string $key = null)
    {
        return $this->request->query($key);
    }

    public function files(string $name = null)
    {
        return $this->request->files($name);
    }

    public function cookie(string $name = null)
    {
        return $this->request->cookie($name);
    }

    public function ip()
    {
        return $this->request->ip();
    }

    public function isXhr()
    {
        return $this->request->isXhr();
    }

    public function isSecure()
    {
        return $this->request->isSecure();
    }
}
