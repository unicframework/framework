<?php

namespace Unic;

use Unic\UploadedFile;

class UploadedFileHandler
{
    private static $files = [];

    private static function parseFiles() {
        if (!empty(self::$files)) {
            return self::$files;
        }
        $files = [];
        foreach ($_FILES as $file => $all) {
            foreach ($all as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $index => $val) {
                        $files[$file][$index][$key] = $val;
                    }
                } else {
                    $files[$file][$key] = $value;
                }
            }
        }
        foreach ($files as $key => $value) {
            if (isset($value['name'])) {
                self::$files[$key] = new UploadedFile($value);
            } else {
                foreach ($value as $index => $val) {
                    self::$files[$key][$index] = new UploadedFile($val);
                }
            }
        }
        return self::$files;
    }

    /**
     * Get all file
     *
     * @return array
     */
    public static function getAll()
    {
        return self::parseFiles();
    }

    /**
     * Get file
     *
     * @param string $name
     * @return array
     */
    public static function get(string $name)
    {
        return self::parseFiles()[$name] ?? null;
    }

    /**
     * Has file
     *
     * @param string $name
     * @return bool
     */
    public static function has(string $name)
    {
        if (!empty($_FILES[$name])) {
            return true;
        } else {
            return false;
        }
    }
}
