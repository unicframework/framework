<?php

namespace Unic\Router;

use Unic\Router\HttpRouterTrait;

class HttpRouter
{
    use HttpRouterTrait;

    public function routes()
    {
        return $this->getRoutes();
    }
}
