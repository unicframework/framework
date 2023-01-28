<?php

namespace Unic\Middleware\Session;

class PHPSession implements ISession
{
    private $sessions;

    public function __construct(array $options = [])
    {
        if (!session_id()) {
            session_start($options);
        }
        $this->sessions = &$_SESSION;
    }

    public function set(string $name, $value = null)
    {
        $this->sessions[$name] = $value;
    }

    public function get(string $name = null)
    {
        if ($name !== null) {
            return $this->sessions[$name] ?? null;
        }
        return (object) $this->sessions;
    }

    public function has(string $name)
    {
        if (isset($this->sessions[$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(string $name)
    {
        if (isset($this->sessions[$name])) {
            unset($this->sessions[$name]);
        }
    }

    public function destroy()
    {
        session_destroy();
    }
};
