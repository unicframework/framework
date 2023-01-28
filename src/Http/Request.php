<?php

namespace Unic\Http;

use Unic\Http\Request\IRequest;
use Unic\Http\Request\PHPRequest;

class Request implements IRequest
{
    // Request info
    public $hostname = null;
    public $port = null;
    public $scheme = null;
    public $method = null;
    public $protocol = null;
    public $accept = null;
    public $language = null;
    public $encoding = null;
    public $contentType = null;
    public $contentLength = null;
    public $userAgent = null;
    public $referrer = null;

    // Request url
    public $baseUrl = null;
    public $path = null;
    public $url = null;
    public $fullUrl = null;
    public $params = null;

    public $app = null;
    private $request = null;
    private static $instance = null;

    public function __construct(&$request, &$app)
    {
        $this->app = $app;

        if ($this->app->config->get('server') === 'php') {
            $this->request = new PHPRequest($request, $this->app);
        } else if ($this->app->config->get('server') === 'openswoole') {
            // TODO
        }

        // Request info
        $this->hostname = $this->request->hostname;
        $this->port = $this->request->port;
        $this->scheme = $this->request->scheme;
        $this->method = $this->request->method;
        $this->protocol = $this->request->protocol;
        $this->accept = $this->request->accept;
        $this->language = $this->request->language;
        $this->encoding = $this->request->encoding;
        $this->contentType = $this->request->contentType;
        $this->contentLength = $this->request->contentLength;
        $this->userAgent = $this->request->userAgent;
        $this->referrer = $this->request->referrer;

        // Request url
        $this->baseUrl = $this->request->baseUrl;
        $this->path = $this->request->path;
        $this->url = $this->request->url;
        $this->fullUrl = $this->request->fullUrl;
        $this->params = $this->request->params;

        self::$instance = $this;
    }

    public static function getInstance()
    {
        return self::$instance;
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

    public function queryString()
    {
        return $this->request->queryString();
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
