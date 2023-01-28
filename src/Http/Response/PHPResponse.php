<?php

namespace Unic\Http\Response;

use COM;
use stdClass;
use Exception;

class PHPResponse implements IResponse
{
    private $headers = [];
    private $content = null;
    private $contentType = null;
    private $encoding = null;
    private $statusCode = null;
    private $filePath = null;
    private $viewPath = null;
    private $viewArgs = null;
    private $isWritable = true;
    private $isEnded = false;
    private $headerSent = false;
    public $locals = null;

    public $app = null;
    private $response = null;

    public function __construct(&$response, &$app)
    {
        $this->app = $app;
        $this->response = $response;
        $this->locals = new stdClass();
    }

    public function headerSent()
    {
        return $this->headerSent;
    }

    private function throwExceptionIfHaderIsSent() {
        if ($this->headerSent()) {
            throw new Exception('Error: header is already sent');
        }
    }

    public function header($header, string $value = null)
    {
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->headers[strtolower($key)] = [
                    'type' => 'set',
                    'header' => $key,
                    'value' => $value,
                ];
            }
        } else {
            $this->headers[strtolower($header)] = [
                'type' => 'set',
                'header' => $header,
                'value' => $value,
            ];
        }
        return $this;
    }

    public function hasHeader(string $header)
    {
        return isset($this->headers[strtolower($header)]['value']) ? $this->headers[strtolower($header)]['value'] : null;
    }

    public function removeHeader($header)
    {
        if (is_array($header)) {
            foreach ($header as $key) {
                $this->headers[strtolower($key)]['type'] = 'remove';
            }
        } else {
            $this->headers[strtolower($header)]['type'] = 'remove';
        }
        return $this;
    }

    public function flushHeaders()
    {
        $this->headers = [];
        return $this;
    }

    public function status(int $httpResponseCode)
    {
        $this->statusCode = $httpResponseCode;
        return $this;
    }

    public function sendStatus(int $httpResponseCode)
    {
        $this->status($httpResponseCode)->end();
    }

    public function write(string $string, string $encoding = null)
    {
        $this->contentType = 'raw';
        $this->content .= $string;
        if ($encoding !== null) {
            $this->encoding = $encoding;
        }
        return $this;
    }

    public function isWritable()
    {
        return $this->isWritable;
    }

    public function send($string, int $httpResponseCode = null)
    {
        $this->contentType = 'html';
        $this->header('Content-Type', 'text/html');
        $this->content = $string;
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end();
    }

    public function render(string $viewPath, array $args = [])
    {
        $this->contentType = 'html';
        $this->header('Content-Type', 'text/html');
        $this->viewPath = $viewPath;
        $this->viewArgs = $args;
        foreach ($this->locals as $key => $value) {
            $this->viewArgs[$key] = $value;
        }
        $this->end();
    }

    public function json($data, int $httpResponseCode = null)
    {
        $this->contentType = 'json';
        $this->header('Content-Type', 'application/json');
        $this->content = $data;
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end();
    }

    public function file(string $filePath, string $mimeType = null, int $httpResponseCode = null)
    {
        $this->contentType = 'file';
        if ($mimeType !== null) {
            $this->header('Content-Type', $mimeType);
        } else {
            $this->header('Content-Type', $this->app->getMimeType($filePath) ?? mime_content_type($filePath));
        }
        $this->header('Content-Length', $this->getFileSize($filePath));

        $this->filePath = $filePath;
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end();
    }

    public function download(string $filePath, string $fileName = null, int $httpResponseCode = null)
    {
        $this->contentType = 'download';
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Disposition', 'attachment; filename=' . ($fileName ?? basename($filePath)));
        $this->header('Expires', 0);
        $this->header('Cache-Control', 'must-revalidate');
        $this->header('Pragma', 'public');
        $this->header('Content-Length', $this->getFileSize($filePath));

        $this->filePath = $filePath;
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end();
    }

    public function redirect(string $url, int $httpResponseCode = null)
    {
        $this->header('Location', $url);
        if ($httpResponseCode !== null) {
            $this->status($httpResponseCode);
        }
        $this->end();
    }

    public function cookie(string $name, string $value = null, array $options = [])
    {
        setcookie(
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
        setcookie($name, null, -1);
        return $this;
    }

    public function end($string = null, string $encoding = null)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        $this->throwExceptionIfHaderIsSent();

        if ($this->isEnded) {
            return;
        }

        // Set default headers
        header('X-Powered-By: Unic Framework');

        // Set response headers
        foreach ($this->headers as $row) {
            if ($row['type'] == 'remove') {
                header_remove($row['header']);
            } else {
                header("{$row['header']}: {$row['value']}");
            }
        }

        // Set http response code
        if (isset($this->statusCode)) {
            http_response_code($this->statusCode);
        }

        if ($this->contentType === 'raw') {
            echo $this->content;
        }

        if ($this->contentType === 'html') {
            if ($this->viewPath !== null) {
                render($this->viewPath, $this->viewArgs, $this->app);
            } else if (gettype($this->content) === 'object') {
                echo json_encode($this->content);
            } else {
                echo $this->content;
            }
        }

        if ($this->contentType === 'json') {
            if (gettype($this->content) == 'object') {
                echo json_encode($this->content);
            } else if ($this->isJson($this->content)) {
                echo $this->content;
            } else {
                echo json_encode($this->content ?? '');
            }
        }

        if ($this->contentType === 'file') {
            readfile($this->filePath);
        }

        if ($this->contentType === 'download') {
            flush();
            readfile($this->filePath);
        }

        $this->isEnded = true;
        $this->headerSent = true;
        $this->isWritable = false;

        ob_end_flush();
    }

    public function flush()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        flush();

        ob_start();

        $this->headers = [];
        $this->content = null;
        $this->contentType = null;
        $this->encoding = null;
        $this->statusCode = null;
        $this->filePath = null;
        $this->viewPath = null;
        $this->viewArgs = null;

        return $this;
    }

    private function isJson($data)
    {
        return is_array($data) ? false : is_array(json_decode($data ?? '', true));
    }

    private function getFileSize(string $filePath)
    {
        $size = filesize($filePath);
        if ($size < 0) {
            if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) {
                $size = trim(`stat -c%s $filePath`);
            } else {
                $fsobj = new COM("Scripting.FileSystemObject");
                $f = $fsobj->GetFile($filePath);
                $size = $f->Size;
            }
        }
        return $size;
    }
}

function render($_self, $_args, $_context)
{
    if ($_context->config->get('view_engine') === 'twig') {
        $loader = new \Twig\Loader\FilesystemLoader($_context->config->get('views'));
        $twig = new \Twig\Environment($loader, $_context->config->getOptions('view_engine') ?? []);

        // Add helper functions
        $twig->addFunction(new \Twig\TwigFunction('route', function ($path) use ($_context) {
            return $_context->route($path);
        }));
        $twig->addFunction(new \Twig\TwigFunction('url', function ($path) use ($_context) {
            return $_context->url($path);
        }));
        $twig->addFunction(new \Twig\TwigFunction('asset', function ($path) use ($_context) {
            return $_context->asset($path);
        }));

        echo $twig->render($_self, $_args);
    } else {
        $_self = $_context->config->get('views') . '/' . trim($_self, '/');

        // Set variables of array.
        foreach ($_args as $variable => $value) {
            ${$variable} = $value;
        }

        // Remove private variables
        unset($_context);
        unset($_args);

        require_once($_self);
    }
}
