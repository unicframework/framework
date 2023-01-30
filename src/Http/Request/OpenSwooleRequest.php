<?php

namespace Unic\Http\Request;

use stdClass;

class OpenSwooleRequest implements IRequest
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

        $this->host = $this->request->header['host'] ?? null;
        $hostnameAndPort = $this->host !== null ? explode(':', $this->host, 2) : null;
        $this->hostname = $hostnameAndPort[0] ?? null;
        $this->port = $hostnameAndPort[1] ?? $this->request->server['server_port'] ?? null;
        $this->method = $this->request->server['request_method'] ?? null;
        $this->method = $this->method !== null ? strtoupper($this->method) : null;
        $this->protocol = $this->request->server['server_protocol'] ?? null;
        $this->url = $this->request->server['request_uri'] ?? null;
        $this->url = $this->url !== '/' ? rtrim($this->url, '/') : $this->url;
        $this->path = parse_url($this->url, PHP_URL_PATH);
        $this->queryString = !empty($this->request->get) ? implode('&', $this->request->get) : null;
        $this->params = new stdClass();
    }

    public function scheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function header(string $header = null)
    {
        if ($header !== null) {
            return $this->request->header[strtolower($header)] ?? null;
        }
        return $this->request->header;
    }

    public function rawHeader()
    {
        if ($this->rawHeader === null) {
            $this->rawHeader = "{$this->method} {$this->url} {$this->protocol}";
            foreach ($this->request->header as $header => $value) {
                $this->rawHeader .= "\r\n" . ucwords(strtolower($header), '-') . ": {$value}";
            }
        }
        return $this->rawHeader;
    }

    public function body(string $key = null)
    {
        if ($this->body === null) {
            $this->body = new stdClass();
            if ($this->header('Content-Type') !== null) {
                if (stripos($this->header('Content-Type'), 'application/x-www-form-urlencoded') !== false) {
                    $urlEncodedString = [];
                    parse_str($this->rawBody() ?? '', $urlEncodedString);
                    foreach ($urlEncodedString as $key => $value) {
                        $this->body->{$key} = $value;
                    }
                } else if (stripos($this->header('Content-Type'), 'application/json') !== false) {
                    $this->body = json_decode($this->rawBody() ?? '');
                } else if (stripos($this->header('Content-Type'), 'multipart/form-data') !== false) {
                    if ($this->method === 'POST') {
                        $this->body = (object) $this->request->post;
                    } else {
                        $this->body = (object) $this->request->rawContent;
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
        return $this->request->getContent();
    }

    public function query(string $key = null)
    {
        if ($this->query === null) {
            $this->query = (object) $this->request->get;
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
            foreach ($this->request->files as $key => $value) {
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
            $this->cookies = (object) $this->request->cookie;
        }

        if ($name !== null) {
            return $this->cookies->{$name} ?? null;
        }
        return $this->cookies;
    }

    public function ip()
    {
        return $this->request->server['remote_addr'] ?? null;
    }

    public function isXhr()
    {
        return $this->header('X-Requested-With') == 'XMLHttpRequest' ? true : false;
    }

    public function isSecure()
    {
        if ($this->isSecure !== null) {
            return $this->isSecure;
        }

        if (intval($this->port ?? 0) === 443) {
            $this->isSecure = true;
        } else if (strtolower($this->header('HTTPS') ?? '') !== 'off') {
            $this->isSecure = true;
        } else if (strtolower($this->header('REQUEST-SCHEME') ?? '') === 'https') {
            $this->isSecure = true;
        } else if (strtolower($this->header('X-FORWARDED-PROTO') ?? '') === 'https') {
            $this->isSecure = true;
        } else if (strtolower($this->header('FRONT-END-HTTPS') ?? '') !== 'off') {
            $this->isSecure = true;
        } else {
            $this->isSecure = false;
        }
        return $this->isSecure;
    }
}
