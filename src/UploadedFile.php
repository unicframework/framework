<?php

namespace Unic;

class UploadedFile
{
    public $name = null;
    public $type = null;
    public $tmpName = null;
    public $size = null;
    public $error = null;

    public function __construct(array &$file) {
        $this->name = $file['name'] ?? null;
        $this->type = $file['type'] ?? null;
        $this->tmpName = $file['tmp_name'] ?? null;
        $this->size = $file['size'] ?? null;
        $this->error = $file['error'] ?? null;
    }

    /**
     * Upload files on the server.
     *
     * @param string $path
     * @param string $filename
     * @return bool
     */
    public function save(string $path, string $fileName = null)
    {
        if ($fileName != null) {
            $path = dirname($path) . '/' . $fileName;
        }
        return move_uploaded_file($this->tmpName, $path);
    }
}
