<?php

namespace Unic\Http\Response;

interface IResponse
{
    public function headerSent();
    public function header($header, string $value = null);
    public function hasHeader(string $header);
    public function removeHeader($header);
    public function flushHeaders();
    public function status(int $httpResponseCode);
    public function sendStatus(int $httpResponseCode);
    public function write(string $string, string $encoding = null);
    public function isWritable();
    public function send($string, int $httpResponseCode = null);
    public function render(string $viewPath, array $args = []);
    public function json($data, int $httpResponseCode = null);
    public function file(string $filePath, string $mimeType = null, int $httpResponseCode = null);
    public function download(string $filePath, string $fileName = null, int $httpResponseCode = null);
    public function redirect(string $url, int $httpResponseCode = null);
    public function cookie(string $name, string $value = null, array $options = []);
    public function removeCookie(string $name);
    public function end($string = null, string $encoding = null);
    public function flush();
}
