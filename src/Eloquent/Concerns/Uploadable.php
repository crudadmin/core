<?php

namespace Admin\Core\Eloquent\Concerns;

use Admin\Core\Helpers\Storage\AdminFile;
use Admin\Core\Helpers\Storage\AdminUploader;
use Illuminate\Filesystem\FilesystemAdapter;
use File;
use Image;
use ImageCompressor;

trait Uploadable
{
    /*
     * Message with error will be stored in this property
     */
    private $uploadErrors = [];

    /*
     * Returns error in upload
     */
    public function getUploadError()
    {
        return implode(', ', $this->uploadErrors);
    }

    /**
     * Returns disk path for uploaded files from actual model
     * [DEPREACED - WE SHOULD REMOVE THIS FUNCTION in v4]
     *
     * @param  string  $fieldKey
     * @param  string  $filename
     * @param  bool  $relative
     * @return string
     */
    public function filePath($fieldKey, $filename = null, $relative = false)
    {
        $directory = $this->getStorageFilePath($fieldKey);

        if ( $relative ) {
            $path = $directory;
        } else {
            $path = $this->getFieldStorage($fieldKey)->path($directory);
        }

        if ($filename) {
            return $path.'/'.$filename;
        }

        return $path;
    }

    /*
     * Check if model has filename modifier
     */
    public function filenameFromAttribute($filename, $field)
    {
        //Filename modifier
        $method_filename_modifier = 'set'.studly_case($field).'Filename';

        //Check if exists filename modifier
        if (method_exists($this, $method_filename_modifier)) {
            $filename = $this->{$method_filename_modifier}($filename);
        }

        return $filename;
    }

    /**
     * Automaticaly check, upload, and make resizing and other function on file object.
     *
     * @param  string     $fieldKey         field key
     * @param  string\UploadedFile     $file          file to upload/download from server
     * @param  bool     $compression
     * @return object
     */
    public function upload(string $fieldKey, $fileOrPathToUpload, $compression = true)
    {
        $uploader = new AdminUploader($this, $fieldKey, $fileOrPathToUpload, $compression);

        if ( !($path = $uploader->upload()) ) {
            return;
        }

        $filename = basename($path);

        return $this->getAdminFile($fieldKey, $filename);
    }

    /*
     * Check if files can be permamently deleted
     */
    public function canPermanentlyDeleteFiles()
    {
        return config('admin.reduce_space', true) === true && $this->delete_files === true;
    }

    /**
     * Remove all uploaded files in existing field attribute
     *
     * @param  string  $key
     * @param  string|array  $newFiles remove only files which are not in array, or given string.
     */
    public function deleteFiles($key, $newFiles = null)
    {
        $storage = $this->getFieldStorage($key);

        //Remove fixed thumbnails
        if (($adminFile = $this->getValue($key)) && ! $this->hasFieldParam($key, 'multiple', true)) {
            $files = array_wrap($adminFile);

            $isAllowedDeleting = $this->canPermanentlyDeleteFiles();

            //Remove also multiple uploaded files
            foreach ($files as $adminFile) {
                $field = $this->getField($key);

                $cachePath = AdminFile::getCacheDirectory(
                    $this->getStorageFilePath($key)
                );

                $needDelete = $newFiles === null
                               || is_array($newFiles) && ! in_array($adminFile->filename, array_flatten($newFiles))
                               || is_string($newFiles) && $adminFile->filename != $newFiles;


                if ( $needDelete ) {
                    //Remove dynamicaly cached thumbnails
                    $this->removeCachedImages($storage, $adminFile, $cachePath);

                    //Removing original files
                    if ($isAllowedDeleting) {
                        $adminFile->delete();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * When file has been deleted, we need remove files from cache directories
     *
     * @param  FilesystemAdapter  $storage
     * @param  AdminFile  $adminFile
     * @param  string  $cachePath
     */
    private function removeCachedImages(FilesystemAdapter $storage, AdminFile $adminFile, $cachePath)
    {
        $resizedImages = $storage->allFiles($cachePath);

        foreach ($resizedImages as $pathToCheck) {
            $cachedFilename = basename($pathToCheck);

            //If filename is same
            if ( $cachedFilename != $adminFile->filename ) {
                continue;
            }

            //Delete filename
            $storage->delete($pathToCheck);

            $webpPath = $pathToCheck.'.webp';

            //Remove also webp version of image
            if ( $storage->exists($webpPath) ) {
                $storage->delete($webpPath);
            }
        }
    }
}
