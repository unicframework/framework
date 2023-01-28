<?php

namespace Unic\Middleware;

use Unic\Http\Request;
use Unic\Http\Response;
use Unic\Middleware\Session\PHPSession;

trait MiddlewareTrait
{
    public function session(array $options = [])
    {
        return function (Request $req, Response $res, $next) use ($options) {
            $req->session = new PHPSession($options);
            $next();
        };
    }

    public function static(string $url, string $path, array $options = [])
    {
        $publicDirPath = rtrim(trim($path), '/');
        $url = trim(trim($url), '/');
        $publicUrl = empty($url) ? '' : $url . '/';
        return function (Request $req, Response $res, $next) use ($publicDirPath, $publicUrl) {
            $req->app->set('public_dir_path', $publicDirPath);
            $req->app->set('public_url', $publicUrl);
            // Render static file
            if (
                $req->path !== NULL &&
                $publicDirPath !== NULL &&
                $publicUrl !== NULL &&
                preg_match('#^' . $publicUrl . '(.*)$#', $req->path, $matches) &&
                is_file($publicDirPath . '/' . $matches[1])
            ) {
                $filePath = $publicDirPath . '/' . $matches[1];
                $res->status(304)->file($filePath);
            } else {
                $next();
            }
        };
    }
}
