<?php

namespace Unic\Http;

use stdClass;
use Unic\Http\Response\IResponse;
use Unic\Http\Response\PHPResponse;

class Response implements IResponse
{
    public $locals = null;

    private $app = null;
    private $response = null;

    public function __construct(&$response, &$app)
    {
        $this->app = $app;
        if ($this->app->config->get('server') === 'php') {
            $this->response = new PHPResponse($response, $this->app);
        } else if ($this->app->config->get('server') === 'openswoole') {
            // TODO
        }
        $this->locals = new stdClass();
    }

    public function headerSent()
    {
        return $this->response->headerSent();
    }

    public function header($header, string $value = null)
    {
        return $this->response->header($header, $value);
    }

    public function hasHeader(string $header)
    {
        return $this->response->hasHeader($header);
    }

    public function removeHeader($header)
    {
        return $this->response->removeHeader($header);
    }

    public function flushHeaders()
    {
        return $this->response->flushHeaders();
    }

    public function status(int $httpResponseCode)
    {
        return $this->response->status($httpResponseCode);
    }

    public function sendStatus(int $httpResponseCode)
    {
        return $this->response->sendStatus($httpResponseCode);
    }

    public function write(string $string, string $encoding = null)
    {
        return $this->response->write($string, $encoding);
    }

    public function isWritable()
    {
        return $this->response->isWritable();
    }

    public function send($string, int $httpResponseCode = null)
    {
        return $this->response->send($string, $httpResponseCode);
    }

    public function render(string $viewPath, array $args = [])
    {
        return $this->response->render($viewPath, $args);
    }

    public function json($data, int $httpResponseCode = null)
    {
        return $this->response->json($data, $httpResponseCode);
    }

    public function file(string $filePath, string $mimeType = null, int $httpResponseCode = null)
    {
        return $this->response->file($filePath, $mimeType, $httpResponseCode);
    }

    public function download(string $filePath, string $fileName = null, int $httpResponseCode = null)
    {
        return $this->response->download($filePath, $fileName, $httpResponseCode);
    }

    public function redirect(string $url, int $httpResponseCode = null)
    {
        return $this->response->redirect($url, $httpResponseCode);
    }

    public function cookie(string $name, string $value = null, array $options = [])
    {
        return $this->response->cookie($name, $value, $options);
    }

    public function removeCookie(string $name)
    {
        return $this->response->removeCookie($name);
    }

    public function end($string = null, string $encoding = null)
    {
        return $this->response->end($string, $encoding);
    }

    public function flush()
    {
        return $this->response->flush();
    }
}
