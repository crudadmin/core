<?php

namespace Admin\Core\Helpers\Storage;

use AdminCore;
use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Helpers\Storage\Concerns\FileHelper;
use Admin\Core\Helpers\Storage\Concerns\HasDownloads;
use Admin\Core\Helpers\Storage\Concerns\HasResizer;
use Admin\Core\Helpers\Storage\Mutators\EncryptorMutator;
use File;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Storage;

class AdminFile implements Arrayable, JsonSerializable
{
    use FileHelper,
        HasDownloads,
        HasResizer;

    /**
     * This directories will be visible for file downloads
     */
    const UPLOADS_DIRECTORY = 'uploads';
    const CACHE_DIRECTORY = 'cache';

    /*
     * Related admin model
     */
    public $table;

    /*
     * Related field key
     */
    public $fieldKey;

    /*
     * Related model id
     */
    public $rowId;

    /**
     * File Disk
     *
     * @var  string
     */
    public $disk;

    /*
     * Relative path to file in disk storage
     */
    public $path;

    /*
     * Previous given object
     */
    private $originalObject;

    /**
     * Return full url addresses to array responses
     *
     * @var  bool
     */
    static $isApi = false;

    /**
     * Create new admin file
     *
     * @param  string  $path
     * @param  string|null  $fieldKey
     * @param  string|null  $path
     * @param  string|null  $disk
     */
    public function __construct(AdminModel $model, string $fieldKey, string $path, $disk = null)
    {
        $this->table = $model->getTable();

        $this->fieldKey = $fieldKey;

        $this->rowId = $model->getKey();

        $this->disk = $disk ?: $model->getFieldDiskName($fieldKey);

        $this->path = $path;
    }

    /**
     * Format the instance as a string using the set format.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url();
    }

    /**
     * Returns array value for file
     *
     * @return  String
     */
    public function toArray()
    {
        if ( self::$isApi === true ) {
            return $this->url();
        }

        return $this->filename;
    }

    /*
     * Formatting for json
     *
     * @return  string
     */
    public function jsonSerialize()
    {
        return $this->url;
    }

    /**
     * Forward property calls
     *
     * @param  string  $key
     *
     * @return  mixed
     */
    public function __get($key)
    {
        //Forward calls into methods
        if ( in_array($key, ['url', 'filename', 'filesize', 'basepath', 'extension', 'hash', 'exists', 'download']) ) {
            return $this->{$key}();
        }

        return $this->{$key};
    }

    public function isEncrypted()
    {
        return str_ends_with($this->path, EncryptorMutator::ENCRYPTOR_EXTENSION);
    }

    /**
     * Returns file storage
     *
     * @return  Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStorage()
    {
        return AdminCore::getDiskByName($this->disk);
    }

    /**
     * Returns cache storage
     *
     * @return  Illuminate\Filesystem\FilesystemAdapter
     */
    public function getCacheStorage()
    {
        if ( $this->isExternalStorageResizer() ) {
            return $this->getStorage();
        }

        //Custom disk name
        if ( $diskName = $this->getCacheStorageDiskName() ) {
            return AdminCore::getDiskByName($diskName);
        }

        return AdminCore::getUploadsStorage();
    }

    /**
     * Returns cache storage name
     */
    public function getCacheStorageDiskName()
    {
        $diskName = config('admin.resizer.storage');

        if ( is_string($diskName) ) {
            return $diskName;
        }

        //Save on local disk
        else if ( $diskName === false ){
            return AdminCore::getUploadsStorageName();
        }

        //Save on remote disk
        else if ( $diskName === true ) {
            return $this->disk;
        }
    }

    /**
     * Build file url path
     *
     * @param  string  $path
     *
     * @return  string
     */
    public function url()
    {
        if ( $this->isResized() ) {
            if (
                //If is internal storage, we can use storage url, because
                //if image is missing, laravel endpoint is waiting and will process image
                $this->isLocalStorage()

                //We can return resized storage path image url.
                //This is usefull when we does not have CDN available. Then we can cache internaly
                //if resized image is available, and redirect directly to the storage url
                || ($this->hasStorageExistanceCache() && self::isExternalStorageResizer() && $this->existsCached())
            ) {
                $url = $this->getCacheStorage()->url($this->path);
            }

            //Use resizer endpoint with asset route for CDN support
            //We need resize image in crudadmin request, because image probably does not exists.
            else {
                $url = asset(route('crudadminResizer', [$this->table, $this->fieldKey, $this->cachePrefix, $this->filename], false));
            }
        }

        //Source image
        else {
            $url = $this->getStorage()?->url($this->path);
        }

        //TODO: REFACTOR
        //We can generate webp image for resource that should be
        // $isUploadsDir = substr(ltrim($path, '/\\'), 0, 7) == self::UPLOADS_DIRECTORY;
        // if ( $isUploadsDir && class_exists('Admin') && \Admin::isFrontend() ){
        //     $this->createWebp(public_path($path));
        // }

        if (
            config('admin.frontend_editor')
            && class_exists(\FrontendEditor::class)
            && $this->table && $this->fieldKey && $this->rowId
        ) {
            return \FrontendEditor::buildImageQuery($url, $this->resizeParams, $this->table, $this->fieldKey, $this->rowId);
        }

        return $url;
    }

    /**
     * Returns content of file
     *
     * @return  mixed
     */
    public function get()
    {
        $data = $this->getStorage()->get($this->path);

        if ( $this->isEncrypted() ){
            return EncryptorMutator::decrypt($data);
        }

        return $data;
    }

    /**
     * Returns base64 file version
     *
     * @return  string
     */
    public function getBase64()
    {
        $base64 = base64_encode($this->get());

        return 'data:;base64,'.$base64;
    }

    /**
     * Returns filename
     *
     * @return  string|null
     */
    public function filename()
    {
        return basename($this->path);
    }

    /**
     * Returns file basepath
     *
     * @return  string|null
     */
    public function basepath()
    {
        return $this->getStorage()->path($this->path);
    }

    /*
     * Returns extension name of file
     */
    public function extension($filename = null)
    {
        $filename = $filename ?: $this->filename;
        $filename = str_replace_last(EncryptorMutator::ENCRYPTOR_EXTENSION, '', $filename);

        $extension = explode('.', $filename);

        return last($extension);
    }

    /**
     * Set model table
     *
     * @param  string  $table
     */
    public function setTable(string $table)
    {
        $this->table = $table;

        return $this;
    }

    /*
     * Set field key
     *
     * @param  string  $fieldKey
     */
    public function setFieldKey(string $fieldKey)
    {
        $this->fieldKey = $fieldKey;

        return $this;
    }

    /**
     * Set row id
     *
     * @param  int  $rowId
     */
    public function setRowId($rowId)
    {
        $this->rowId = $rowId;

        return $this;
    }

    /**
     * Set disk of file
     *
     * @param  string  $disk
     */
    public function setDisk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Returns file model
     *
     * @return  AdminModel
     */
    public function getModel()
    {
        return AdminCore::getModelByTable($this->table);
    }

    /*
     * If directories for postprocessed images dones not exists
     */
    public static function makeDirs($path)
    {
        if (! file_exists($path)) {
            File::makeDirectory($path, 0775, true);
        }
    }

    /*
     * Remove file
     */
    public function delete()
    {
        if ($this->exists()) {
            return $this->getStorage()->delete($this->path);
        }

        return false;
    }

    /*
     * Remove file alias
     */
    public function remove()
    {
        $delete = $this->delete();

        $this->flushExistanceFromCache();

        return $delete;
    }

    /*
     * Check if file exists
     */
    public function exists()
    {
        return $this->getStorage()->exists($this->path);
    }

    /*
     * Copy file to destination directory
     */
    public function copy($destination)
    {
        if ($this->exists) {
            return $this->getStorage()->copy($this->path, $destination);
        }

        return false;
    }

    /**
     * Return filesize in specific format.
     * @param  bolean $formated
     * @return string/integer
     */
    public function filesize($formated = false)
    {
        if ($formated === true) {
            return $this->filesizeFormated();
        }

        return $this->getStorage()->size($this->path);
    }

    /*
     * Returns formated value of filesize
     */
    public function filesizeFormated()
    {
        $bytes = sprintf('%u', $this->filesize);

        return static::formatFilesizeNumber($bytes);
    }

    /*
     * Format filesize number
     */
    public static function formatFilesizeNumber($bytes)
    {
        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));
            $units = ['B', 'KB', 'MB', 'GB'];

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    /*
     * Clone required params form frontendEditor
     */
    public function cloneModelData($file)
    {
        $this->originalObject = $file;
        $this->resizeParams = $file->resizeParams;
        $this->cachePrefix = $file->cachePrefix;
        $this->table = $file->table;
        $this->fieldKey = $file->fieldKey;
        $this->rowId = $file->rowId;

        return $this;
    }

    public function getOriginalObject()
    {
        return $this->originalObject;
    }
}
