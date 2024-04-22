<?php

namespace Admin\Core\Migrations\Concerns;

use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type as DBType;
use Doctrine\DBAL\Types\JsonArrayType;
use Illuminate\Database\DBAL\TimestampType;

trait MigrationSupport
{
    /**
     * Laravel < 11 fallback
     *
     * @return  bool
     */
    public function hasDoctrine()
    {
        return method_exists(app('db'), 'registerDoctrineType');
    }

    public function doctrineFallback($callback)
    {
        if ( !$this->hasDoctrine() ){
            return;
        }

        $callback();
    }

    public function registerMigrationSupport()
    {
        $this->doctrineFallback(function(){
            $platform = DB::getDoctrineSchemaManager()->getDatabasePlatform();

            //Enums
            $platform->registerDoctrineTypeMapping('enum', 'string');

            //Timestamps
            DBType::addType('timestamp', TimestampType::class);

            //Add json support
            if (! DBType::hasType('json')) {
                DBType::addType('json', JsonArrayType::class);
                $platform->registerDoctrineTypeMapping('json', 'string');
            }
        });
    }
}
