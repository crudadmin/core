<?php

namespace Admin\Core\Helpers\Storage\Concerns;

use AdminCore;
use Cache;

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
     * Check if file exists in filestorage. And cache file existance
     *
     * @param  string|null  $path
     * @param  bool  $force
     *
     * @return  bool
     */
    public function existsCached($path = null, $force = false)
    {
        $path = $path ?: $this->path;

        if ( $this->isLocalStorage() || $force === true ) {
            return $this->getStorage()->exists($path);
        }

        $key = $this->getFilepathExistanceCacheKey($path);
        $period = $this->getExistanceCachePeriod();

        return Cache::remember($key, $period, function() use ($path) {
            return $this->getStorage()->exists($path);
        });
    }

    public function flushExistanceFromCache()
    {
        if ( $this->isLocalStorage() == false ){
            Cache::forget($this->getFilepathExistanceCacheKey($this->path));
        }

        return $this;
    }

    public function setCachedFileExistance($path, bool $state)
    {
        if ( $this->isLocalStorage() === false ) {
            $key = $this->getFilepathExistanceCacheKey($path);
            $period = $this->getExistanceCachePeriod();

            Cache::put($key, $state, $period);
        }

        return $this;
    }

    private function getExistanceCachePeriod()
    {
        $cacheDays = config('admin.storage_exists_cache', 31);

        return 60 * 60 * 24 * $cacheDays;
    }

    private function getFilepathExistanceCacheKey($path)
    {
        return 'admin_file_cache.'.$path;
    }
}