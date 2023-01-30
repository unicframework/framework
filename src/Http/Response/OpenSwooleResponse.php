<?php

namespace Unic\Http\Response;

use Unic\Helpers\Helpers;
use stdClass;

class OpenSwooleResponse implements IResponse
{
    private $headers = [];
    private $headerSent = false;
    public $locals = null;

    public $app = null;
    private $response = null;

    public function __construct(&$response, &$app)
    {
        $this->app = $app;
        $this->response = $response;
        $this->locals = new stdClass();
        // Set default headers
        $this->header('X-Powered-By', 'Unic Framework');
    }

    public function headerSent()
    {
        return $this->headerSent;
    }

    public function header($header, string $value = null)
    {
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->headers[ucwords(strtolower($key), '-')] = $value;
                $this->response->header($key, $value);
            }
        } else {
            $this->headers[ucwords(strtolower($header), '-')] = $value;
            $this->response->header($header, $value);
        }
        return $this;
    }

    public function hasHeader(string $header)
    {
        return $this->headers[ucwords(strtolower($header), '-')] ?? null;
    }

    public function removeHeader($header)
    {
        if (is_array($header)) {
            foreach ($header as $key) {
                if (isset($this->headers[ucwords(strtolower($key), '-')])) {
                    unset($this->headers[ucwords(strtolower($key), '-')]);
                    $this->response->header($key, null);
                }
            }
        } else {
            if (isset($this->headers[ucwords(strtolower($header), '-')])) {
                unset($this->headers[ucwords(strtolower($header), '-')]);
                $this->response->header($header, null);
            }
        }
        return $this;
    }

    public function flushHeaders()
    {
        foreach ($this->headers as $key) {
            unset($this->headers[$key]);
            $this->response->header($key, null);
        }
        $this->headers = [];
        return $this;
    }

    public function status(int $httpResponseCode)
    {
        $this->response->status($httpResponseCode);
        return $this;
    }

    public function sendStatus(int $httpResponseCode)
    {
        $this->status($httpResponseCode)->end();
    }

    public function write(string $string, string $encoding = null)
    {
        $this->response->write($string);
        return $this;
    }

    public function isWritable()
    {
        return $this->response->isWritable();
    }

    public function send($string, int $httpResponseCode = null)
    {
        $this->header('Content-Type', 'text/html');
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end($string);
    }

    public function render(string $viewPath, array $args = [])
    {
        $this->header('Content-Type', 'text/html');
        foreach ($this->locals as $key => $value) {
            $args[$key] = $value;
        }
        ob_start();
        $this->app->config->getOptions('view_engine')['render']($viewPath, $args, $this->app);
        $this->end(ob_get_clean());
    }

    public function json($data, int $httpResponseCode = null)
    {
        $this->header('Content-Type', 'application/json');
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        if (gettype($data) == 'object') {
            $this->end(json_encode($data));
        } else if (Helpers::isJson($data)) {
            $this->end($data);
        } else {
            $this->end(json_encode($data ?? ''));
        }
    }

    public function file(string $filePath, string $mimeType = null, int $httpResponseCode = null)
    {
        if ($mimeType !== null) {
            $this->header('Content-Type', $mimeType);
        } else {
            $this->header('Content-Type', Helpers::getMimeType($filePath) ?? mime_content_type($filePath));
        }
        $this->header('Content-Length', Helpers::getFileSize($filePath));

        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->response->sendfile($filePath);
        $this->headerSent = true;
    }

    public function download(string $filePath, string $fileName = null, int $httpResponseCode = null)
    {
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Disposition', 'attachment; filename=' . ($fileName ?? basename($filePath)));
        $this->header('Expires', 0);
        $this->header('Cache-Control', 'must-revalidate');
        $this->header('Pragma', 'public');
        $this->header('Content-Length', Helpers::getFileSize($filePath));

        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->response->sendfile($filePath);
        $this->headerSent = true;
    }

    public function redirect(string $url, int $httpResponseCode = null)
    {
        if ($httpResponseCode !== null) {
            $this->response->redirect($url, $httpResponseCode);
        } else {
            $this->response->redirect($url);
        }
        $this->headerSent = true;
    }

    public function cookie(string $name, string $value = null, array $options = [])
    {
        $this->response->cookie(
            $name,
            $value,
            $options['expire'] ?? null,
            $options['path'] ?? null,
            $options['domain'] ?? null,
            $options['secure'] ?? null,
            $options['httpOnly'] ?? null
        );
        return $this;
    }

    public function removeCookie(string $name)
    {
        $this->response->cookie($name, null, -1);
        return $this;
    }

    public function end($string = null, string $encoding = null)
    {
        $this->headerSent = true;
        $this->response->end($string);
    }

    public function flush()
    {
        $this->flushHeaders();
        return $this;
    }
}
