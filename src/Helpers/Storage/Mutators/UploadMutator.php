<?php

namespace Admin\Core\Helpers\Storage\Mutators;

use Admin\Core\Eloquent\AdminModel;
use Illuminate\Filesystem\FilesystemAdapter;

class UploadMutator
{
    protected $storage;

    protected $model;

    protected $fieldKey;

    protected $path;

    protected $filename;

    protected $extension;

    public function __construct(FilesystemAdapter $storage, AdminModel $model, string $fieldKey, string $path, string $filename, $extension)
    {
        $this->storage = $storage;

        $this->model = $model;

        $this->fieldKey = $fieldKey;

        $this->path = $path;

        $this->filename = $filename;

        $this->extension = $extension;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getFieldKey()
    {
        return $this->fieldKey;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getExtension()
    {
        return $this->extension;
    }
}
