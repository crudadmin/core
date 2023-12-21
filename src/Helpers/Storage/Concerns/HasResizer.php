<?php

namespace Admin\Core\Helpers\Storage\Concerns;

use Cache;
use File;
use Image;
use ImageCompressor;
use AdminCore;

trait HasResizer
{
    /*
     * Saved resize params
     */
    private $resizeParams = [];

    /*
     * Cache prefix directory
     */
    private $cachePrefix;

    /*
     * Get resized parameters
     */
    public function getResizeParams()
    {
        return $this->resizeParams;
    }

    public function isResized()
    {
        return $this->cachePrefix ? true : false;
    }

    /*
     * Get cache prefix
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
    }

    /**
     * Set cache prefix
     *
     * @param  string  $cachePrefix
     */
    public function setCachePrefix($cachePrefix)
    {
        $this->cachePrefix = $cachePrefix;

        return $this;
    }

    public static function isExternalStorageResizer()
    {
        return config('admin.resizer.storage', false) === true;
    }

    /**
     * Returns backup images resource path
     *
     * @return  string
     */
    protected function getBackupResourcePath()
    {
        return config('admin.backup_image', __DIR__.'/../../../Resources/images/thumbnail.jpg');
    }

    /*
     * Resize or fit image depending on dimensions
     */
    public function resize($width = null, $height = null, $force = false)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if ( $this->canBeImageResized() === false ) {
            return $this;
        }

        //Saved resize params
        $this->resizeParams = [$width, $height];

        if (is_numeric($width) && is_numeric($height)) {
            $action = 'fit';
        } else {
            $action = 'resize';
        }

        return $this->image([
            $action => [$width, $height],
        ], $force);
    }

    /**
     * Resize image.
     *
     * @param  array   $mutators
     * @param  bool $force
     *
     * @return File/Image class
     */
    public function image($mutators = [], $force = false, $cacheDirectory = null)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if ( $this->canBeImageResized() === false ) {
            return $this;
        }

        //Prefix of directory for given resize parameters configuration
        $cachePrefix = $cacheDirectory ?: $this->getCacheMutatorsDirectory($mutators);

        //Get directory path for file
        $cachedPath = $this->getCachePath($cachePrefix, $mutators);

        //If previously resized image has been resized from sample image, when source is missing...
        $this->checkInactiveSampleImage($cachedPath);

        //If image processign is ask to be completed in actual request
        if ($force === true) {
            //Set image for processing if does not exists yet
            if ( $this->existsCached($cachedPath, false, $this->getCacheStorage()) == false ) {
                $this->processImageMutators($cachedPath, $mutators);
            }
        } else {
            $this->setCachedResizeData($cachePrefix, $mutators);
        }

        return (new static($this->getModel(), $this->fieldKey, $cachedPath))
                ->cloneModelData($this)
                ->setCachePrefix($cachePrefix);
    }

    /**
     * Return existing image class or delete existing sample file
     *
     * @param  string  $cachedPath
     */
    private function checkInactiveSampleImage($cachedPath)
    {
        //If is not local storage, we cannot allow checking of sample images
        if ( $this->isLocalStorage() === false ) {
            return;
        }

        $samplePath = $this->getBackupCacheImageName($cachedPath);

        //If original/source image does exists and sample file is generated
        //In this case we need reset all resized cache, which has been resized from sample image.
        if ( !($this->exists() && $this->getStorage()->exists($samplePath)) ){
            return;
        }

        $this->getStorage()->delete([
            $samplePath, $cachedPath
        ]);
    }

    /**
     * Process given mutators/resizes
     *
     * @param  string  $destinationPath
     * @param  array  $mutators
     *
     * @return  Image
     */
    private function processImageMutators($destinationPath, $mutators)
    {
        $model = $this->getModel();

        $backupImageIfSourceMissing = $this->exists() === false;

        $adminStorage = AdminCore::getUploadsStorage();

        //Load example image if source is missing. Or load from storage.
        $imageData = $backupImageIfSourceMissing
                        ? $this->getBackupResourcePath()
                        : $this->get();

        $image = Image::make($imageData);

        /*
         * Apply mutators on image
         */
        foreach ($mutators as $mutator => $params) {
            $params = $this->paramsMutator($mutator, $params);

            $image = call_user_func_array([$image, $mutator], $params);
        }

        //Save image on local disk with compression
        ImageCompressor::saveImageWithCompression(
            $adminStorage,
            $model,
            $image,
            $destinationPath,
            $this->extension
        );

        //Create webp version of image
        //We want create webP image before ImageCompressor runs, because otherwise PNG image may be bigger size
        $this->createWebp($destinationPath, $image);

        //Label that this rendered image has been switched for default/backup image.
        //If image will appear, we will delete this sample file.
        if ( $backupImageIfSourceMissing ) {
            $this->getCacheStorage()->put($this->getBackupCacheImageName($destinationPath), '');
        }

        //If storage cache is turned on. We will send resized cache images to the storage
        $model->moveToFinalStorage($this->fieldKey, $destinationPath, $this->getCacheStorage());

        $this->setCachedFileExistance($destinationPath, true);

        return $image;
    }

    /**
     * Update postprocess params
     *
     * @param  string  $name
     * @param  mixed  $params
     *
     * @return  array
     */
    public function paramsMutator($name, $params)
    {
        $params = array_wrap($params);

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
     * Returns sample image postfix extension
     *
     * @param  string  $path
     *
     * @return  string
     */
    private function getBackupCacheImageName($path)
    {
        return $path.'.backup';
    }


    /**
     * Create webp version of image file into final storage destination
     *
     * @param  string  $sourcePath
     * @param  Image  $image
     *
     */
    public function createWebp(string $sourcePath, $image = null)
    {
        $sourcePath = $sourcePath ?: $this->basepath;

        $outputFilepath = $sourcePath.'.webp';

        if ( $this->canCreateWebpFormat($sourcePath, $outputFilepath) == false ){
            return $this;
        }

        //Get compressed saved image from local crudadmin storage path
        $image = $image ?: Image::make(
            AdminCore::getUploadsStorage()->get($sourcePath)
        );

        $encoded = $image->encode('webp', 85);

        $this->getCacheStorage()->put($outputFilepath, $encoded);

        return $this;
    }

    /**
     * Check if webp format can be created
     *
     * @param $sourcePath
     * @param $outputFilepath
     *
     * @return  bool
     */
    private function canCreateWebpFormat($sourcePath, $outputFilepath)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif'];

        //If webp images are not enabled
        if ( config('admin.image_webp', false) === false ){
            return false;
        }

        //If webp format is not allowed for this file
        if ( !in_array(strtolower($this->extension ?: $this->extension($sourcePath)), $allowedExtensions) ) {
            return false;
        }

        //If webp exists already
        if ( $this->getStorage()->exists($outputFilepath) == true ) {
            return false;
        }

        return true;
    }

    /**
     * Build cache path
     *
     * @param  string  $cachePrefix
     * @param  string  $mutators
     *
     * @return  string
     */
    private function getCachePath($cachePrefix, $mutators = null)
    {
        //Get directory path for file
        $path = dirname($this->path).'/'.$cachePrefix.'/'.$this->filename;

        return self::getCacheDirectory($path);
    }

    /**
     * Returns cache directory postfix
     *
     * @param  string  $path
     *
     * @return  string
     */
    public static function getCacheDirectory($path)
    {
        return self::CACHE_DIRECTORY.'/'.$path;
    }

    /**
     * Return public version of cache directory
     *
     * @return  string
     */
    public static function getPublicCacheDirectory()
    {
        return (self::isCacheInRootFolder() ? '' : self::UPLOADS_DIRECTORY.'/').self::CACHE_DIRECTORY;
    }

    /**
     * Is cache saved instead of /uploads/cache in /cache ?
     *
     * @return  bool
     */
    public static function isCacheInRootFolder()
    {
        return is_string(config('admin.resizer.storage'));
    }

    private function canBeImageResized()
    {
        if ( config('admin.image_rewrite_missing_uploads', true) === false ) {
            return false;
        }

        if ( $this->extension == 'svg' ) {
            return false;
        }

        return true;
    }

    /**
     * Build directory name according to given mutators
     *
     * @param  string  $mutators
     *
     * @return  string
     */
    private function getCacheMutatorsDirectory($mutators)
    {
        $hash = [];

        foreach ($mutators as $mk => $mutatorRow) {
            $subHash = [];

            foreach ($mutatorRow as $key => $mutator) {
                if (! is_string($mutator) && ! is_numeric($mutator)) {
                    $subHash[] = $mutator ? 1 : 0;
                } else {
                    $subHash[] = $mutator;
                }
            }

            $hash[] = implode('-', [$mk, implode('x', $subHash)]);
        }

        return implode('_', $hash);
    }

    /**
     * Cache key for preparing resize in next request
     *
     * @params $cachePrefix
     *
     * @return  string
     */
    public function getCacheKey($cachePrefix)
    {
        return 'resize.'.$this->table.'.'.$this->fieldKey.'.'.$cachePrefix;
    }

    /**
     * Returns cached resize information
     *
     * @param  string  $cachePrefix
     * @param  array  $mutators
     *
     * @return  array|null
     */
    public function setCachedResizeData($cachePrefix, $mutators)
    {
        $cacheKey = $this->getCacheKey($cachePrefix);

        //Does not add into cache if exists already
        if ( Cache::has($cacheKey) ) {
            return;
        }

        //Put directory resize settings
        Cache::forever($cacheKey, [
            'mutators' => $mutators,
        ]);
    }

    /**
     * Returns cached resize information
     *
     * @param  string  $cachePrefix
     *
     * @return  array|null
     */
    public function getCachedResizeData($cachePrefix)
    {
        return Cache::get(
            $this->getCacheKey($cachePrefix)
        );
    }
}