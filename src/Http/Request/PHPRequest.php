<?php

namespace Unic\Http\Request;

use stdClass;

class PHPRequest implements IRequest
{
    // Request info
    public $host = null;
    public $hostname = null;
    public $port = null;
    private $ip = null;
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
    private $query = null;
    private $queryString = null;

    // Request input
    private $body = null;
    private $files = null;
    private $cookies = null;

    private $headers = null;
    private $rawHeader = null;

    public $app = null;
    private $request = null;

    public function __construct(&$request, &$app)
    {
        $this->app = $app;
        $this->request = $request;
        $this->host = $this->request['HTTP_HOST'] ?? null;
        $this->hostname = $this->request['SERVER_NAME'] ?? null;
        $this->port = $this->request['SERVER_PORT'] ?? null;
        $this->scheme = (isset($this->request['HTTPS']) && ($this->request['HTTPS'] === 'on' || $this->request['HTTPS'] === 1)) || (isset($this->request['HTTP_X_FORWARDED_PROTO']) && $this->request['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($this->request['HTTP_FRONT_END_HTTPS']) && strtolower($this->request['HTTP_FRONT_END_HTTPS']) !== 'off') ? 'https' : 'http';
        $this->method = isset($this->request['REQUEST_METHOD']) ? strtoupper($this->request['REQUEST_METHOD']) : null;
        $this->protocol = $this->request['SERVER_PROTOCOL'] ?? null;
        $this->accept = $this->request['HTTP_ACCEPT'] ?? null;
        $this->language = $this->request['HTTP_ACCEPT_LANGUAGE'] ?? null;
        $this->encoding = $this->request['HTTP_ACCEPT_ENCODING'] ?? null;
        $this->contentType = $this->request['CONTENT_TYPE'] ?? $this->request['HTTP_CONTENT_TYPE'] ?? null;
        $this->contentLength = $this->request['CONTENT_LENGTH'] ?? $this->request['HTTP_CONTENT_LENGTH'] ?? null;
        $this->userAgent = $this->request['HTTP_USER_AGENT'] ?? null;
        $this->referrer = $this->request['HTTP_REFERER'] ?? null;

        $this->baseUrl = $this->scheme . '://' . $this->host;
        $this->path = trim(parse_url($this->request['REQUEST_URI'], PHP_URL_PATH), '/');
        $this->url = rtrim($this->request['REQUEST_URI'], '/') ?? null;
        $this->fullUrl = $this->scheme . '://' . $this->host . $this->url;
        $this->params = new stdClass();
    }

    public function header(string $header = null)
    {
        if ($this->headers === null) {
            $this->headers = [];
            foreach ($this->request as $key => $value) {
                if (substr($key, 0, 5) !== 'HTTP_') {
                    continue;
                }
                $headerName = ucwords(str_replace('_', '-', strtolower(substr($key, 5))), '-');
                $this->headers[$headerName] = $value;
            }
        }

        if ($header !== null) {
            return $this->headers[ucwords(strtolower($header), '-')] ?? null;
        }
        return $this->headers;
    }

    public function rawHeader()
    {
        if ($this->rawHeader === null) {
            $this->rawHeader = "{$this->method} {$this->url} {$this->protocol}";
            foreach ($this->header() as $header => $value) {
                $this->rawHeader .= "\r\n{$header}: {$value}";
            }
        }
        return $this->rawHeader;
    }

    public function body(string $key = null)
    {
        if ($this->body === null) {
            $contentType = strtolower($this->contentType ?? '');
            $this->body = new stdClass();
            if ($contentType != null) {
                if ($contentType == 'application/x-www-form-urlencoded') {
                    $bodyStringList = explode('&', $this->rawBody() ?? '');
                    foreach ($bodyStringList as $row) {
                        $tmp = explode('=', $row, 2);
                        if (isset($tmp[0]) && $tmp[0] !== '') {
                            $this->body->{$tmp[0]} = $tmp[1] ?? null;
                        }
                    }
                } else if ($contentType == 'application/json') {
                    $this->body = json_decode($this->rawBody() ?? '');
                } else if (preg_match('/multipart\/form-data;/', $contentType)) {
                    if (strtoupper($this->method()) === 'POST') {
                        $this->body = (object) $_POST;
                    } else {
                        $this->body = (object) $_REQUEST;
                    }
                }
            }
        }
        if ($key !== null) {
            return $this->body->{$key} ?? null;
        }
        return $this->body;
    }

    public function rawBody()
    {
        return file_get_contents('php://input');
    }

    public function query(string $key = null)
    {
        if ($this->query === null) {
            $this->query = (object) $_GET;
        }
        if ($key !== null) {
            return $this->query->{$key} ?? null;
        }
        return $this->query;
    }

    public function queryString()
    {
        if ($this->queryString === null) {
            $this->queryString = $this->request['QUERY_STRING'] ?? null;
        }
        return $this->queryString;
    }

    public function files(string $name = null)
    {
        if ($this->files === null) {
            $this->files = new stdClass();
            $files = [];
            foreach ($_FILES as $file => $all) {
                foreach ($all as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $index => $val) {
                            $files[$file][$index][$key] = $val;
                        }
                    } else {
                        $files[$file][$key] = $value;
                    }
                }
            }
            foreach ($files as $key => $value) {
                if (isset($value['name'])) {
                    $this->files->{$key} = new stdClass();
                    $this->files->{$key}->name = $value['name'] ?? null;
                    $this->files->{$key}->mimeType = $value['type'] ?? null;
                    $this->files->{$key}->path = $value['tmp_name'] ?? null;
                    $this->files->{$key}->size = $value['size'] ?? null;
                    $this->files->{$key}->error = $value['error'] ?? null;
                } else {
                    foreach ($value as $index => $val) {
                        $this->files->{$key}[$index] = new stdClass();
                        $this->files->{$key}[$index]->name = $val['name'] ?? null;
                        $this->files->{$key}[$index]->mimeType = $val['type'] ?? null;
                        $this->files->{$key}[$index]->path = $val['tmp_name'] ?? null;
                        $this->files->{$key}[$index]->size = $val['size'] ?? null;
                        $this->files->{$key}[$index]->error = $val['error'] ?? null;
                    }
                }
            }
        }
        if ($name !== null) {
            return $this->files->{$name} ?? null;
        }
        return $this->files;
    }

    public function cookie(string $name = null)
    {
        if ($this->cookies === null) {
            $this->cookies = (object) $_COOKIE;
        }

        if ($name !== null) {
            return $this->cookies->{$name} ?? null;
        }
        return $this->cookies;
    }

    public function ip()
    {
        // Get user ip
        return $this->request['REMOTE_ADDR'] ?? $this->request['SERVER_ADDR'] ?? null;
    }

    public function isXhr()
    {
        return isset($this->request['HTTP_X_REQUESTED_WITH']) && $this->request['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ? true : false;
    }

    public function isSecure()
    {
        return strtolower($this->scheme) == 'https' ? true : false;
    }
}
