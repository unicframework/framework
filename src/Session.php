<?php

namespace Unic;

class Session
{
    /**
     * Set Session
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function set(string $name, $value = '')
    {
        // Start session
        if (!session_id()) {
            session_start();
        }
        $_SESSION[$name] = $value;
    }

    /**
     * Get Session
     *
     * @param string $name
     * @return string|void
     */
    public static function get(string $name)
    {
        // Start session
        if (!session_id()) {
            session_start();
        }
        return $_SESSION[$name] ?? null;
    }

    /**
     * Has Session
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name)
    {
        // Start session
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete Session
     *
     * @param string $data
     * @return void
     */
    public static function delete(string $name)
    {
        // Start session
        if (!session_id()) {
            session_start();
        }
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * Destroy Session
     *
     * @return void
     */
    public static function destroy()
    {
        // Start session
        if (!session_id()) {
            session_start();
        }
        session_destroy();
    }
}
