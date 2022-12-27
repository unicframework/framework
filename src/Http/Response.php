<?php

namespace Unic\Http;

use COM;
use Exception;
use stdClass;
use Unic\Config;

class Response
{
    private $responseType = null;
    private $headers = [];
    private $content = null;
    private $statusCode = null;
    private $targetUrl = null;
    private $file = null;
    private $view = null;
    private $args = null;
    private $isWritable = true;
    private $isEnd = false;
    public $locals = null;
    private static $instance = null;

    private function __construct()
    {
        $this->locals = new stdClass();
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Response();
        }
        return self::$instance;
    }

    public function headerIsSent()
    {
        return !$this->isWritable;
    }

    private function throwExceptionIfHeaderIsSent()
    {
        if ($this->isWritable == false) {
            throw new Exception('Header is already sent');
        } else {
            return false;
        }
    }

    /**
     * Set Header
     * Set http response header.
     *
     * @param mixed $header
     * @return void
     */
    public function header($header, string $value = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->headers[] = [
                    'type' => 'set',
                    'header' => $key,
                    'value' => $value,
                ];
            }
        } else {
            $this->headers[] = [
                'type' => 'set',
                'header' => $header,
                'value' => $value,
            ];
        }
        return $this;
    }

    /**
     * Remove Header
     * Set http response header.
     *
     * @param mixed $header
     * @return void
     */
    public function removeHeader($header, string $value = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->headers[] = [
                    'type' => 'remove',
                    'header' => $key,
                    'value' => $value,
                ];
            }
        } else {
            $this->headers[] = [
                'type' => 'remove',
                'header' => $header,
                'value' => $value,
            ];
        }
        return $this;
    }

    /**
     * Set Response Code
     * Set http response status code.
     *
     * @param int $httpResponseCode
     * @return void
     */
    public function status(int $httpResponseCode)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->statusCode = $httpResponseCode;
        return $this;
    }

    /**
     * Response Code
     * Set http response status code.
     *
     * @param int $httpResponseCode
     * @return void
     */
    public function sendStatus(int $httpResponseCode)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->statusCode = $httpResponseCode;
        $this->isWritable = false;
    }

    /**
     * Redirect URLs
     * Redirect users to another page.
     *
     * @param string $url
     * @param string $method
     * @param int $httpResponseCode
     * @return void
     */
    public function redirect(string $url, int $httpResponseCode = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'redirect';
        // IIS environment use 'refresh' for better compatibility
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false) {
            $method = 'refresh';
        }
        // Redirect
        if (isset($method)) {
            if (strtolower($method) === 'refresh') {
                $this->header('Refresh', '0; url=' . $url);
            }
        } else {
            $this->header('Location', $url);
        }
        $this->targetUrl = $url;
        $this->statusCode = $httpResponseCode ?? $this->statusCode ?? 302;
        $this->isWritable = false;
    }

    /**
     * Response
     * Render string data with HTTP Response status code.
     *
     * @param mixed $string
     * @param int $httpResponseCode
     * @return void
     */
    public function send($string, int $httpResponseCode = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'text';
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        $this->content = $string;
        $this->statusCode = $httpResponseCode ?? $this->statusCode ?? 200;
        $this->isWritable = false;
    }

    /**
     * Response
     * Render html.
     *
     * @param string $viewPath
     * @param array $args
     * @return void
     */
    public function render(string $viewPath, array $args = [])
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'html';
        $this->header('Content-Type', 'text/html; charset=UTF-8');
        $this->view = $viewPath;
        $this->args = $args;
        foreach ($this->locals as $key => $value) {
            $this->args[$key] = $value;
        }
        $this->isWritable = false;
    }

    /**
     * Json Data
     * Check Json format is valid or not.
     *
     * @param mixed $data
     * @return boolean
     */
    private function isJson($data)
    {
        return is_array($data) ? false : is_array(json_decode($data ?? '', true));
    }

    /**
     * JSON Response
     * Render json data with HTTP Response status code.
     *
     * @param mixed $data
     * @param int $httpResponseCode
     * @return void
     */
    public function json($data, int $httpResponseCode = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'json';
        $this->header('Content-Type', 'application/json; charset=UTF-8');
        $this->content = $data;
        $this->statusCode = $httpResponseCode ?? $this->statusCode ?? 200;
        $this->isWritable = false;
    }

    /**
     * Render File
     * Render files with HTTP Response status code.
     *
     * @param string $filePath
     * @param string $mimeType
     * @param int $httpResponseCode
     * @return void
     */
    public function file(string $filePath, string $mimeType = null, int $httpResponseCode = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'file';
        // Set header content type.
        if ($mimeType != null) {
            $this->header('Content-Type', $this->mimeType);
        } else {
            $this->header('Content-Type', mime_content_type($filePath));
        }
        $this->header('Content-Length', $this->getFileSize($filePath));

        $this->file = $filePath;
        $this->statusCode = $httpResponseCode ?? $this->statusCode ?? 200;
        $this->isWritable = false;
    }

    /**
     * Send File
     * Send files to the client.
     *
     * @param string $filePath
     * @param string $fileName
     * @param int $httpResponseCode
     * @return void
     */
    public function sendFile(string $filePath, string $fileName = null, int $httpResponseCode = null)
    {
        $this->throwExceptionIfHeaderIsSent();
        $this->responseType = 'download';
        // Set header content type.
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Description', 'File Transfer');
        $this->header('Content-Disposition', 'attachment; filename=' . ($fileName ?? basename($filePath)));
        $this->header('Expires', 0);
        $this->header('Cache-Control', 'must-revalidate');
        $this->header('Pragma', 'public');
        $this->header('Content-Length', $this->getFileSize($filePath));

        $this->file = $filePath;
        $this->statusCode = $httpResponseCode ?? $this->statusCode ?? 200;
        $this->isWritable = false;
    }

    /**
     * Get File Size
     * get file size of any file, support larger then 4 GB file size.
     *
     * @param string $filePath
     * @return int
     */
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

    public function end()
    {
        if ($this->isEnd == true) {
            return;
        }

        $this->isEnd = true;

        ob_clean();
        ob_start();

        // Set default headers
        header('X-Powered-By: unic framework');

        // Set headers
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

        if ($this->responseType == 'text') {
            if (gettype($this->content) == 'object') {
                echo json_encode($this->content);
            } else {
                echo $this->content;
            }
        }

        if ($this->responseType == 'html') {
            renderView($this->view, $this->args);
        }

        if ($this->responseType == 'json') {
            if (gettype($this->content) == 'object') {
                echo json_encode($this->content);
            } else if ($this->isJson($this->content)) {
                echo $this->content;
            } else {
                echo json_encode($this->content ?? '');
            }
        }

        if ($this->responseType == 'file') {
            readfile($this->file);
        }

        if ($this->responseType == 'download') {
            flush();
            readfile($this->file);
        }

        ob_end_flush();
    }
}

function renderView(string $_self, array $_args = [])
{
    if (Config::get('view_engine') == 'twig') {
        $loader = new \Twig\Loader\FilesystemLoader(Config::get('views'));
        $twig = new \Twig\Environment($loader, [ 'cache' => Config::get('cache_path') ?? false]);
        echo $twig->render($_self, $_args);
    } else {
        // Set variables of array.
        foreach ($_args as $variable => $value) {
            ${$variable} = $value;
        }
        require_once(Config::get('views') . '/' . trim($_self, '/'));
    }
}
