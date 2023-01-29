<?php

namespace Unic\Http\Request;

use stdClass;

class PHPRequest implements IRequest
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

    private $ip = null;
    private $query = null;
    private $body = null;
    private $files = null;
    private $cookies = null;
    private $headers = null;
    private $rawHeader = null;
    private $isSecure = null;

    public $app = null;
    private $request = null;

    public function __construct(&$request, &$app)
    {
        $this->app = $app;
        $this->request = $request;

        $this->host = $this->request['HTTP_HOST'] ?? null;
        $this->hostname = $this->request['SERVER_NAME'] ?? null;
        $this->port = $this->request['SERVER_PORT'] ?? null;
        $this->method = $this->request['REQUEST_METHOD'] ?? $this->request['HTTP_METHOD'] ?? null;
        $this->method = $this->method !== null ? strtoupper($this->method) : null;
        $this->protocol = $this->request['SERVER_PROTOCOL'] ?? null;
        $this->url = $this->request['REQUEST_URI'] ?? null;
        $this->url = $this->url !== '/' ? rtrim($this->url, '/') : $this->url;
        $this->path = parse_url($this->request['REQUEST_URI'], PHP_URL_PATH);
        $this->queryString = $this->request['QUERY_STRING'] ?? null;
        $this->params = new stdClass();
    }

    public function scheme() {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function header(string $header = null)
    {
        if ($this->headers === null) {
            $this->headers = [];
            foreach ($this->request as $key => $value) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $headerName = ucwords(str_replace('_', '-', strtolower(substr($key, 5))), '-');
                    $this->headers[$headerName] = $value;
                }
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
            $this->body = new stdClass();
            if ($contentType != null) {
                if (stripos($this->header('Content-Type') ?? '', 'application/x-www-form-urlencoded') !== false) {
                    $urlEncodedString = [];
                    parse_str($this->rawBody() ?? '', $urlEncodedString);
                    foreach ($urlEncodedString as $key => $value) {
                        $this->body->{$key} = $value;
                    }
                } else if (stripos($this->header('Content-Type') ?? '', 'application/json') !== false) {
                    $this->body = json_decode($this->rawBody() ?? '');
                } else if (stripos($this->header('Content-Type') ?? '', 'multipart/form-data') !== false) {
                    if ($this->method === 'POST') {
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
        return $this->request['REMOTE_ADDR'] ?? null;
    }

    public function isXhr()
    {
        return isset($this->request['HTTP_X_REQUESTED_WITH']) && $this->request['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ? true : false;
    }

    public function isSecure()
    {
        if ($this->isSecure !== null) {
            return $this->isSecure;
        }

        if (isset($this->request['SERVER_PORT']) && intval($this->request['SERVER_PORT']) === 443) {
            $this->isSecure = true;
        } else if (isset($this->request['HTTPS']) && strtolower($this->request['HTTPS']) !== 'off') {
            $this->isSecure = true;
        } else if (isset($this->request['REQUEST_SCHEME']) && strtolower($this->request['REQUEST_SCHEME']) === 'https') {
            $this->isSecure = true;
        } else if (isset($this->request['HTTP_X_FORWARDED_PROTO']) && strtolower($this->request['HTTP_X_FORWARDED_PROTO']) === 'https') {
            $this->isSecure = true;
        } else if (isset($this->request['HTTP_FRONT_END_HTTPS']) && strtolower($this->request['HTTP_FRONT_END_HTTPS']) !== 'off') {
            $this->isSecure = true;
        } else {
            $this->isSecure = false;
        }
        return $this->isSecure;
    }
}
