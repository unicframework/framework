<?php

namespace Unic\Helpers;

trait HelperTrait
{
    public function asset(string $path = '')
    {
        return $this->url(rtrim($this->config->get('public_url'), '/') . '/' . ltrim($path, '/'));
    }

    public function url(string $path = '')
    {
        return !empty($this->context['request']) ? $this->context['request']->scheme . '://' . $this->context['request']->host . '/' . trim($path, '/') : '/';
    }

    public function route(string $name, array $params = [])
    {
        $path = $this->getNamedRoute($name, $params);
        if ($path != null) {
            return $this->url($path);
        }
        return null;
    }
}
