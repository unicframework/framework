<?php

namespace Unic\Middleware\Session;

interface ISession
{
    public function set(string $name, $value = null);
    public function get(string $name = null);
    public function has(string $name);
    public function delete(string $name);
    public function destroy();
}
