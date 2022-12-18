<?php

namespace Unic;

class UploadedFileHandler
{
    /**
     * Get all file
     *
     * @return array|null
     */
    public static function getAll()
    {
        return $_FILES ?? null;
    }

    /**
     * Get file
     *
     * @param string $name
     * @return array|null
     */
    public static function get(string $name)
    {
        return $_FILES[$name] ?? null;
    }

    /**
     * Has file
     *
     * @param string $name
     * @return bool
     */
    public static function has(string $name)
    {
        if (isset($_FILES[$name])) {
            return true;
        } else {
            return false;
        }
    }
}
