<?php

namespace Admin\Core\Contracts;

trait HasConfig
{
    private static $config;

    public function cacheConfig()
    {
        static::$config = config('admin');

        return $this;
    }

    public function config()
    {
        if ( static::$config === null ){
            $this->cacheConfig();
        }

        return static::$config;
    }
}
