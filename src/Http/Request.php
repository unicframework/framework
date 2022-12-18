<?php

namespace Unic\Http;

use stdClass;
use Unic\Cookie;
use Unic\UploadedFileHandler;
use Unic\Session;

class Request
{
    // Request info
    public $hostname = null;
    public $port = null;
    private $ip = null;
    public $scheme = null;
    private $method = null;
    public $protocol = null;
    private $secure = null;
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

    // Request input
    private $body = null;
    public $cookies = null;
    public $sessions = null;
    public $files = null;

    private $isXhr = null;

    private static $instance = null;

    private function __construct()
    {
        // Request info

        // Get hostname
        $this->hostname = $_SERVER['SERVER_NAME'] ?? null;

        // Get port
        $this->port = $_SERVER['SERVER_PORT'] ?? null;

        // Get scheme
        $this->scheme = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ? 'https' : 'http';

        // Get protocol
        $this->protocol = $_SERVER['SERVER_PROTOCOL'] ?? null;

        // Get accept content type
        $this->accept = $_SERVER['HTTP_ACCEPT'] ?? null;

        // Get accept language
        $this->language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        // Get http encoding
        $this->encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? null;

        // Get content type
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;

        // Get content length
        $this->contentLength = $_SERVER['CONTENT_LENGTH'] ?? $_SERVER['HTTP_CONTENT_LENGTH'] ?? null;

        // Get user agent
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Get http referer
        $this->referrer = $_SERVER['HTTP_REFERER'] ?? null;


        // Request url

        // Get site base url
        $this->baseUrl = $this->scheme . '://' . $_SERVER['HTTP_HOST'];

        // Get request path without query string
        $this->path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Get request url
        $this->url = $_SERVER['REQUEST_URI'] ?? null;

        // Get site full url
        $this->fullUrl = $this->scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Get url parameters
        $this->params = new stdClass();


        // Request input

        // Get files data
        $this->files = new UploadedFileHandler();

        // Get session data
        $this->sessions = new Session();

        // Get cookie data
        $this->cookies = new Cookie();

    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Request();
        }
        return self::$instance;
    }

    public function header(string $header)
    {
        return $_SERVER[strtoupper($header)] ?? null;
    }

    public function rawBody()
    {
        return file_get_contents('php://input');
    }

    public function body()
    {
        // Parse request body
        if ($this->body != null) {
            return $this->body;
        }
        $contentType = strtolower($this->contentType ?? '');
        $this->body = new stdClass();
        if ($contentType != null) {
            if ($contentType == 'application/x-www-form-urlencoded') {
                $bodyStringList = explode('&', $this->body);
                foreach ($bodyStringList as $row) {
                    $tmp = explode('=', $row);
                    $this->body->{$tmp[0]} = $tmp[1] ?? null;
                }
            } else if ($contentType == 'application/json') {
                $this->body = json_decode($this->body() ?? '');
            } else if (preg_match('/multipart\/form-data;/', $contentType)) {
                $this->body = (object) $_REQUEST;
            }
        }
      return $this->body;
    }

    public function queryString()
    {
        // Get query string
        return $_SERVER['QUERY_STRING'] ?? null;
    }

    public function query()
    {
        // Get query string
        if ($this->query != null) {
            return $this->query;
        }
        $queryStringList = explode('&', $this->queryString() ?? '') : [];
        $this->query = new stdClass();
        foreach ($queryStringList as $row) {
            $tmp = explode('=', $row);
            $this->query->{$tmp[0]} = $tmp[1];
        }
        return $this->query;
    }

    public function method() {
        // Get request method get, post, put, delete
        if ($this->method != null) {
            return $this->method;
        }
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
        return $this->method;
    }

    public function isMethod(string $method) {
        return strtolower($method) == $this->method();
    }

    public function ip() {
        // Get user ip
        return $_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function isXhr()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ? true : false;
    }

    public function isSecure()
    {
        if ($this->secure != null) {
            return $this->secure;
        }
        $this->secure = strtolower($this->scheme) == 'https' ? true : false;
        return $this->secure;
    }
}
