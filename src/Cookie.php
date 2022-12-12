<?php

namespace Unic;

class Cookie
{
    /**
     * Set Cookie
     *
     * @param string $name
     * @param string $value
     * @param integer $expire
     * @param string $path
     * @param string $domain
     * @param boolean $secure
     * @param boolean $httponly
     * @return boolean
     */
    public static function set(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false)
    {
        if (isset($value)) {
            //Set cookie data
            if (setcookie($name, $value, $expire, $path, $domain, $secure, $httponly)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get Cookie
     *
     * @param string $name
     * @return string|void
     */
    public static function get(string $name)
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Has Cookie
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name)
    {
        if (isset($_COOKIE[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete Cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return boolean
     */
    public static function delete(string $name, string $path = '/', string $domain = '')
    {
        return setcookie($name, '', -1, $path, $domain);
    }
}
