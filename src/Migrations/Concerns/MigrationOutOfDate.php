<?php

namespace Admin\Core\Migrations\Concerns;

use Cache;
use Admin\Core\Eloquent\AdminModel;

trait MigrationOutOfDate
{
    /**
     * Check if AdminModel is up to date.
     * @param  object  $model
     * @param  closure  $migration
     * @return bool
     */
    protected function isOutOfDate(AdminModel $model, $migration)
    {
        $path = (new \ReflectionClass($model))->getFileName();

        //If file class does not exists
        if (! file_exists($path)) {
            //Migrate
            call_user_func($migration);

            return false;
        }

        $cacheKey = 'admin_migrations.'.md5(get_class($model));

        $hash = $this->getModelUpdateHash($model, $path);

        if ($this->option('force') === false && Cache::get($cacheKey) == $hash) {
            return true;
        }

        //Migrate
        call_user_func($migration);

        //Cache model after migration done
        Cache::forever($cacheKey, $hash);

        return false;
    }

    /*
     * Generate hash of configruation for given model
     */
    public function getModelUpdateHash($model, $path)
    {
        $fields = $model->getFields();

        try {
            $fieldsTimestamp = json_encode($fields);
        } catch (\Exception $e) {
            $fieldsTimestamp = implode(';', array_keys($fields));
        }

        return md5(implode('-', [
            md5_file($path),
            $fieldsTimestamp
        ]));
    }
}
