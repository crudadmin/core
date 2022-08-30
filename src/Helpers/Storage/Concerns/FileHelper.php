<?php

namespace Admin\Core\Helpers\Storage\Concerns;

use AdminCore;
use Cache;
use Illuminate\Filesystem\FilesystemAdapter;

trait FileHelper
{
    private $isLocalStorage;

    /**
     * Returns and cache if storage is local uploads storage driver
     *
     * @return  bool
     */
    public function isLocalStorage()
    {
        if ( is_null($this->isLocalStorage) ) {
            return $this->isLocalStorage = ($this->getStorage() == AdminCore::getUploadsStorage());
        }

        return $this->isLocalStorage;
    }

    /**
     * Returns whatever existance cache is turned on
     *
     * @return  bool
     */
    public function hasStorageExistanceCache()
    {
        //If we are not saving resized images into storage, there is no need to activate this feature.
        if ( $this->externalStorageResizer() === false ){
            return false;
        }

        return config('admin.resizer.storage_cache', false);
    }

    /**
     * Check if file exists in filestorage. And cache file existance
     *
     * @param  string|null  $path
     * @param  bool  $force
     * @param  FilesystemAdapter  $storage
     *
     * @return  bool
     */
    public function existsCached($path = null, $force = false, FilesystemAdapter $storage = null)
    {
        $storage = $storage ?: $this->getStorage();
        $path = $path ?: $this->path;

        //If storage existance is turned off.
        //Or when existance is turned on, but is local storage... then we can check immidiatelly
        if ( $this->hasStorageExistanceCache() == false || $this->isLocalStorage() || $force === true ) {
            return $storage->exists($path);
        }

        $key = $this->getFilepathExistanceCacheKey($path);
        $period = $this->getExistanceCachePeriod();

        return Cache::remember($key, $period, function() use ($path, $storage) {
            return $storage->exists($path);
        });
    }

    public function flushExistanceFromCache()
    {
        if ( $this->hasStorageExistanceCache() == true && $this->isLocalStorage() == false ){
            Cache::forget($this->getFilepathExistanceCacheKey($this->path));
        }

        return $this;
    }

    public function setCachedFileExistance($path, bool $state)
    {
        if ( $this->hasStorageExistanceCache() == true && $this->isLocalStorage() === false ) {
            $key = $this->getFilepathExistanceCacheKey($path);
            $period = $this->getExistanceCachePeriod();

            Cache::put($key, $state, $period);
        }

        return $this;
    }

    private function getExistanceCachePeriod()
    {
        $cacheDays = config('admin.resizer.storage_cache_days', 31);

        return 60 * 60 * 24 * $cacheDays;
    }

    private function getFilepathExistanceCacheKey($path)
    {
        return 'admin_file_cache.'.$path;
    }
}