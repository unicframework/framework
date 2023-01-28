<?php

namespace Unic\Http\Request;

interface IRequest
{
    public function header(string $header = null);
    public function rawHeader();
    public function body(string $key = null);
    public function rawBody();
    public function query(string $key = null);
    public function queryString();
    public function files(string $name = null);
    public function cookie(string $name = null);
    public function ip();
    public function isXhr();
    public function isSecure();
}
