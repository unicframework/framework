<?php

namespace Unic\Http;

use stdClass;
use Unic\Cookie;
use Unic\UploadedFileHandler;
use Unic\Session;

class Request
{
    public $baseUrl = null;
    public $body = null;
    public $cookies = null;
    public $sessions = null;
    public $files = null;
    public $scheme = null;
    public $hostname = null;
    public $port = null;
    public $ip = null;
    public $protocol = null;
    public $method = null;
    public $url = null;
    public $fullUrl = null;
    public $params = null;
    public $path = null;
    public $query = null;
    public $secure = null;
    public $isXhr = null;
    public $accept = null;
    public $language = null;
    public $encoding = null;
    public $contentType = null;
    public $contentLength = null;
    public $userAgent = null;
    public $referrer = null;

    public function __construct()
    {
        /**
         * Request Body
         * 
         * Get http request body information.
         */
        $this->body = file_get_contents('php://input');

        // Get files data
        $this->files = new UploadedFileHandler();

        // Get session data
        $this->sessions = new Session();

        // Get cookie data
        $this->cookies = new Cookie();

        // Get scheme
        $this->scheme = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1)) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ? 'https' : 'http';

        // Get hostname
        $this->hostname = $_SERVER['SERVER_NAME'] ?? null;

        // Get port
        $this->port = $_SERVER['SERVER_PORT'] ?? null;

        // Get user ip
        $this->ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        // Get request method get, post, put, delete
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);

        // Get protocol
        $this->protocol = $_SERVER['SERVER_PROTOCOL'] ?? null;

        // Get query string
        $queryStringList = isset($_SERVER['QUERY_STRING']) ? explode('&', $_SERVER['QUERY_STRING']) : [];
        $this->query = new stdClass();
        foreach ($queryStringList as $row) {
            $tmp = explode('=', $row);
            $this->query->{$tmp[0]} = $tmp[1];
        }

        // Get site base url
        $this->baseUrl = $this->scheme . '://' . $_SERVER['HTTP_HOST'];

        // Get request url
        $this->url = $_SERVER['REQUEST_URI'] ?? null;

        // Get site full url
        $this->fullUrl = $this->scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Get request path without query string
        $this->path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // Get http_accept
        $this->accept = $_SERVER['HTTP_ACCEPT'] ?? null;

        // Get accept language
        $this->language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        // Get http encoding
        $this->encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? null;

        // Get content type, request MIME type from header
        $this->contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : (isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : null);

        // Get content length
        $this->contentLength = isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : (isset($_SERVER['HTTP_CONTENT_LENGTH']) ? $_SERVER['HTTP_CONTENT_LENGTH'] : null);

        // Get user agent
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // Get http referer
        $this->referrer = $_SERVER['HTTP_REFERER'] ?? null;

        $this->secure = $this->scheme == 'HTTPS' ? true : false;

        $this->isXhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ? true : false;
    }

    public function header(string $header)
    {
        return $_SERVER[strtoupper($header)] ?? null;
    }
}
