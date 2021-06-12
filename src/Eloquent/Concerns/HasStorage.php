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

    /**
     * If file has been saved in crudadmin storage for postprocess operations,
     * but we use other storage for given field. We need send final file into this final storage destination.
     *
     * We will sent in there and remove temporary file for process/mutations purposes
     *
     * @param  string  $fieldKey
     * @param  string  $fileStoragePath
     */
    public function moveToFinalStorage(string $fieldKey, $fileStoragePath)
    {
        $fieldStorage = $this->getFieldStorage($fieldKey);

        $localStorage = Storage::disk('crudadmin');

        //If file should be sent into other storage than temporary crudadmin storage
        if ( $fieldStorage === $localStorage ) {
            return;
        }

        $fieldStorage->writeStream(
            $fileStoragePath,
            $localStorage->readStream($fileStoragePath)
        );

        //After file process we can delete file from temporary crudadmin location
        $localStorage->delete($fileStoragePath);
    }
}
