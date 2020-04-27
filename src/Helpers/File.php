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

        $this->url = $this->buildUrlPath($this->path);
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

    private function buildUrlPath($path)
    {
        $isUploadsDir = substr(ltrim($path, '/'), 0, 7) == 'uploads';

        $url = asset($path);

        //We can generate webp image for resource that should be
        if ( $isUploadsDir && class_exists('Admin') && \Admin::isFrontend() ){
            $this->createWebp(public_path($path));
        }

        if ( class_exists(\FrontendEditor::class)
            && $this->tableName && $this->fieldKey && $this->rowId
        ) {
            return \FrontendEditor::buildImageQuery($url, $this->resizeParams, $this->tableName, $this->fieldKey, $this->rowId);
        }

        return $url;
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
        $table = basename($table);
        $field = basename($field);

        $parts = [$table, $field, basename($file)];
        $parts = array_filter($parts);
        $parts = implode('/', $parts);

        $file = new static(public_path('uploads/'.$parts));

        $file->tableName = $table;
        $file->fieldKey = $field;
        $file->rowId = basename($rowId);

        //Rebuild url path. Because this parameter is assigned to model attributes
        $file->url = $file->buildUrlPath($file->path);

        return $file;
    }

    /*
     * Build directory path for caching resized images model
     */
    public static function adminModelCachePath($path = null, $absolute = true)
    {
        $cachePath = 'uploads/cache';

        if ($absolute) {
            return public_path($cachePath.'/'.$path);
        }

        return $cachePath.'/'.$path;
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

        $origPath = substr(trim($this->path, '/'), 8);
        $path = explode('/', $origPath);

        $action = action( '\Admin\Controllers\DownloadController@signedDownload', self::getHash($origPath) );

        return $action.'?model='.urlencode($path[0]).'&field='.urlencode($path[1]).'&file='.urlencode($path[2]);
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

    private function getDirectoryHash($directory, $mutators)
    {
        if ($directory) {
            $hash = str_slug($directory);
        } elseif (count($mutators) > 1) {
            $hash = md5($this->directory.serialize($mutators));
        } else {
            $firstValue = array_first($mutators);

            foreach ($firstValue as $key => $mutator) {
                if (! is_string($mutator) && ! is_numeric($mutator)) {
                    $firstValue[$key] = 0;
                }
            }

            $hash = key($mutators).'-'.implode('x', $firstValue);
        }

        return $hash;
    }

    /**
     * Resize image.
     * @param  array   $mutators      array of muttators
     * @param  [type]  $directory     where should be image saved, directory name may be generated automatically
     * @param  bool $force         force render image immediately
     * @param  bool $return_object return image instance
     * @return File/Image class
     */
    public function image($mutators = [], $directory = null, $force = false, $return_object = false)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if (
            ($this->extension == 'svg' || ! file_exists($this->path))
            && config('admin.image_rewrite_missing_uploads', true) !== true
        ) {
            return $this;
        }

        //Hash of directory which belongs to image mutators
        $hash = $this->getDirectoryHash($directory, $mutators);

        //Correct trim directory name
        $directory = ltrim($this->directory, '/');
        $directory = substr($directory, 0, 8) == 'uploads/' ? substr($directory, 8) : $directory;

        //Get directory path for file
        $cachePath = self::adminModelCachePath($directory.'/'.$hash);

        //Filepath
        $filepath = $cachePath.'/'.$this->filename;

        //Create directory if is missing
        static::makeDirs($cachePath);

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
        $this->createWebp($filepath);

        //Compress image with lossless compression
        if ( class_exists('ImageCompressor') ) {
            \ImageCompressor::tryShellCompression($filepath);
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
    public function resize($width = null, $height = null, $directory = null, $force = false)
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
        ], $directory, $force, false);
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
    public function createWebp($sourcePath = null)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif'];

        $sourcePath = $sourcePath ?: $this->basepath;

        if (
            //If webp images are not enabled
            config('admin.image_webp', false) === false

            //If webp format is not allowed for this file
            || !in_array($this->extension ?: $this->getExtension($sourcePath), $allowedExtensions)

            //If source path does not exists
            || !file_exists($sourcePath)

            //If webp exists already
            || file_exists($outputFilepath = $sourcePath.'.webp')
        ){
            return $this;
        }

        $image = Image::make($sourcePath);

        $encoded = $image->encode('webp', 85);

        @file_put_contents($outputFilepath, $encoded);

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
