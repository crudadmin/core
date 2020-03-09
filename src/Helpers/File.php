<?php

namespace Admin\Core\Helpers;

use Image;
use File as BaseFile;

class File
{
    /*
     * Filename
     */
    public $filename;

    /*
     * File extension type
     */
    public $extension;

    /*
     * relative path to file
     */
    public $directory;

    /*
     * relative path to file
     */
    public $path;

    /*
     * full basepath
     */
    public $basepath;

    /*
     * Absolute path to file
     */
    public $url;

    /*
     * Related admin model
     */
    public $tableName;

    /*
     * Related field key
     */
    public $fieldKey;

    /*
     * Related model id
     */
    public $rowId;

    /*
     * Saved resize params
     */
    public $resizeParams = [];

    /**
     * Initialize new admin file
     *
     * @param  string  $basepath
     */
    public function __construct($basepath, $previousObject = null)
    {
        //If previous object does exists
        //We want clone some data...
        if ( $previousObject ) {
            $this->cloneModelData($previousObject);
        }

        $this->filename = basename($basepath);

        $this->extension = $this->getExtension($this->filename);

        $this->path = str_replace(public_path('/'), '', $basepath);

        $this->basepath = $basepath;

        $this->directory = implode('/', array_slice(explode('/', $this->path), 0, -1));

        $this->url = $this->buildAssetsPath($this->path);
    }

    /**
     * Format the instance as a string using the set format.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->url;
    }

    public function __get($key)
    {
        $basepath = public_path($this->directory.'/'.$key.'/'.$this->filename);

        return new static($basepath, $this);
    }

    private function buildAssetsPath($path)
    {
        $path = asset($path);

        if ( class_exists(\FrontendEditor::class)
            && $this->tableName && $this->fieldKey && $this->rowId
        ) {
            return \FrontendEditor::buildImageQuery($path, $this->resizeParams, $this->tableName, $this->fieldKey, $this->rowId);
        }

        return $path;
    }

    /*
     * Returns extension name of file
     */
    protected function getExtension($filename)
    {
        $extension = explode('.', $filename);

        return last($extension);
    }

    /*
     * Build directory path for uploaded files in model
     */
    public static function adminModelFile($table, $field, $file, $rowId = null)
    {
        $file = new static(public_path('uploads/'.$table.'/'.$field.'/'.$file));

        $file->tableName = $table;
        $file->fieldKey = $field;
        $file->rowId = $rowId;

        return $file;
    }

    /*
     * Build directory path for caching resized images model
     */
    public static function adminModelCachePath($path = null, $absolute = true)
    {
        $cache_path = 'uploads/cache';

        if ($absolute) {
            return public_path($cache_path.'/'.$path);
        }

        return $cache_path.'/'.$path;
    }

    public static function getHash($path)
    {
        return sha1(md5('!$%'.sha1(env('APP_KEY')).$path));
    }

    /*
     * Returns absolute signed path for downloading file
     */
    public function download($displayableInBrowser = false)
    {
        //If is possible open file in browser, then return right path of file and not downloading path
        if ($displayableInBrowser) {
            if (in_array($this->extension, (array) $displayableInBrowser)) {
                return $this->url;
            }
        }

        $path = substr($this->path, 8);
        $action = action('\Admin\Controllers\DownloadController@signedDownload', self::getHash($path));

        return $action.'?file='.urlencode($path);
    }

    /*
     * Update postprocess params
     */
    public static function paramsMutator($name, $params)
    {
        if (! is_array($params)) {
            $params = [$params];
        }

        //Automatic aspect ratio in resizing image with one parameter
        if ($name == 'resize' && count($params) <= 2) {
            //Add auto ratio
            if (count($params) == 1) {
                $params[] = null;
            }

            $params[] = function ($constraint) {
                $constraint->aspectRatio();
            };
        }

        return $params;
    }

    /**
     * Returns backup images resource path
     *
     * @return  string
     */
    public function getBackupResourcePath()
    {
        return config('admin.backup_image', __DIR__.'/../Resources/images/thumbnail.jpg');
    }

    /**
     * Resize image.
     * @param  array   $mutators      array of muttators
     * @param  [type]  $directory     where should be image saved, directory name may be generated automatically
     * @param  bool $force         force render image immediately
     * @param  bool $return_object return image instance
     * @param  bool $webp          enable/disable webp image extension
     * @return File/Image class
     */
    public function image($mutators = [], $directory = null, $force = false, $return_object = false, $webp = true)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if (
            ($this->extension == 'svg' || ! file_exists($this->path))
            && config('admin.rewrite_missing_upload_images', true) !== true
        ) {
            return $this;
        }

        //Hash of directory which belongs to image mutators
        if ($directory) {
            $hash = str_slug($directory);
        } elseif (count($mutators) > 1) {
            $hash = md5($this->directory.serialize($mutators));
        } else {
            $first_value = array_first($mutators);

            foreach ($first_value as $key => $mutator) {
                if (! is_string($mutator) && ! is_numeric($mutator)) {
                    $first_value[$key] = 0;
                }
            }

            $hash = key($mutators).'-'.implode('x', $first_value);
        }

        //Correct trim directory name
        $directory = ltrim($this->directory, '/');
        $directory = substr($directory, 0, 8) == 'uploads/' ? substr($directory, 8) : $directory;

        //Get directory path for file
        $cache_path = self::adminModelCachePath($directory.'/'.$hash);

        //Filepath
        $filepath = $cache_path.'/'.$this->filename;

        //Create directory if is missing
        static::makeDirs($cache_path);

        //If file exists
        if (file_exists($filepath)) {
            $relative_filepath = self::adminModelCachePath($directory.'/'.$hash.'/'.$this->filename, false);

            return new static(public_path($relative_filepath), $this);
        }

        //If mutators file does not exists, and cannot be resized in actual request, then return path to resizing process
        elseif ($force === false) {
            //Save temporary file with properties for next resizing
            if (! file_exists($filepath.'.temp')) {
                file_put_contents($filepath.'.temp', json_encode([
                    'original_path' => $this->path,
                    'mutators' => $mutators,
                ]));
            }

            return new static($filepath, $this);
        }

        //Set image for processing
        $image = Image::make(file_exists($this->basepath) ? $this->basepath : $this->getBackupResourcePath());

        /*
         * Apply mutators on image
         */
        foreach ($mutators as $mutator => $params) {
            $params = static::paramsMutator($mutator, $params);

            $image = call_user_func_array([$image, $mutator], $params);
        }

        //Save image into cache folder
        $image->save($filepath, 85);

        //Create webp version of image
        if ($webp === true && config('admin.upload_webp', false) === true) {
            $this->createWebp($filepath);
        }

        //Return image object
        if ($return_object) {
            return $image;
        }

        return new static($filepath, $this);
    }

    /*
     * If directories for postprocessed images dones not exists
     */
    public static function makeDirs($path)
    {
        if (! file_exists($path)) {
            BaseFile::makeDirectory($path, 0775, true);
        }
    }

    /*
     * Resize or fit image depending on dimensions
     */
    public function resize($width = null, $height = null, $directory = null, $force = false, $webp = true)
    {
        //Saved resize params
        $this->resizeParams = [$width, $height];

        //We cant resize svg files...
        if ($this->extension == 'svg') {
            return $this;
        }

        if (is_numeric($width) && is_numeric($height)) {
            $action = 'fit';
        } else {
            $action = 'resize';
        }

        return $this->image([
            $action => [$width, $height],
        ], $directory, $force, false, $webp);
    }

    /*
     * Remove file
     */
    public function delete()
    {
        if (file_exists($this->basepath)) {
            unlink($this->basepath);
        }
    }

    /*
     * Remove file alias
     */
    public function remove()
    {
        return $this->delete();
    }

    /*
     * Check if file exists
     */
    public function exists()
    {
        return file_exists($this->basepath);
    }

    /*
     * Copy file to destination directory
     */
    public function copy($destination)
    {
        if (file_exists($this->basepath)) {
            return copy($this->basepath, $destination);
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

        return filesize($this->basepath);
    }

    /*
     * Returns formated value of filesize
     */
    public function filesizeFormated()
    {
        $path = $this->basepath;

        $bytes = sprintf('%u', filesize($path));

        return (new static($path))->formatFilesizeNumber($bytes);
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
     * Create webp version of image file
     */
    public function createWebp($source_path = null)
    {
        $source_path = $source_path ?: $this->basepath;

        $output_filename = $source_path.'.webp';

        //If webp exists already
        if (file_exists($output_filename)) {
            return $this;
        }

        $image = Image::make($source_path);

        $encoded = $image->encode('webp', 85);

        @file_put_contents($output_filename, $encoded);

        return $this;
    }

    /*
     * Clone required params form frontendEditor
     */
    public function cloneModelData($file)
    {
        $this->resizeParams = $file->resizeParams;
        $this->tableName = $file->tableName;
        $this->fieldKey = $file->fieldKey;
        $this->rowId = $file->rowId;

        return $this;
    }
}
