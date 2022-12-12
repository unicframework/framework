<?php

namespace Unic;

class UploadedFileHandler
{
    /**
     * Get File
     *
     * @param string $name
     * @return boolean
     */
    public static function get(string $name)
    {
        return $_FILES[$name] ?? null;
    }

    /**
     * Has File
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name)
    {
        if (isset($_FILES[$name])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Upload files on the server.
     *
     * @param string $tmpName
     * @param string $destination
     * @return boolean
     */
    public static function upload(string $tmpName, string $destination)
    {
        return move_uploaded_file($tmpName, $destination);
    }
}
