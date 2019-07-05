<?php

namespace Admin\Core\Migrations\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Cache;

trait MigrationOutOfDate
{
    /**
     * Check if AdminModel is up to date
     * @param  object  $model
     * @param  closure  $migration
     * @return boolean
     */
    protected function isOutOfDate(AdminModel $model, $migration)
    {
        $path = (new \ReflectionClass($model))->getFileName();

        //If file class does not exists
        if ( ! file_exists($path) )
        {
            //Migrate
            call_user_func($migration);

            return false;
        }

        $namespace = 'admin_migrations.' . md5(get_class($model));

        $hash = md5_file($path);

        if ( $this->option('force') === false && Cache::get($namespace) == $hash )
            return true;

        //Migrate
        call_user_func($migration);

        //Cache model after migration done
        Cache::forever($namespace, $hash);

        return false;
    }

}