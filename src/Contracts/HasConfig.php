<?php

namespace Admin\Core\Contracts;

trait HasConfig
{
    private static $config;

    public function cacheConfig()
    {
        static::$config = config('admin');

        //Refresh field if needed
        foreach ($this->getAdminModels() as $model) {
            $model->refreshFields();
        }

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
