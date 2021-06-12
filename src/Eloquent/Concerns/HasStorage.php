<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Core\Helpers\Storage\AdminFile;
use Storage;

trait HasStorage
{
    /**
     * Get field disk name
     *
     * @param  string  $fieldKey
     * @return  string
     */
    public function getFieldDiskName(string $fieldKey)
    {
        return config('admin.disk');
    }

    /**
     * Get storage for given field
     *
     * @param  string  $key
     * @return  Storage
     */
    public function getFieldStorage(string $fieldKey)
    {
        return Storage::disk(
            $this->getFieldDiskName($fieldKey)
        );
    }

    /**
     * Returns file location for given field
     *
     * @param  string  $fieldKey
     * @return  string
     */
    public function getStorageFilePath(string $fieldKey)
    {
        return $this->getTable().'/'.$fieldKey;
    }

    /**
     * Returns admin model File class
     *
     * @param  string  $fieldKey
     * @param  string  $filename
     *
     * @return  AdminFile
     */
    public function getAdminFile(string $fieldKey, $filename = null)
    {
        if ( ! $filename ){
            return;
        }

        $path = $this->getStorageFilePath($fieldKey).'/'.$filename;

        $file = new AdminFile($this, $fieldKey, $path);

        return $file;
    }

}
