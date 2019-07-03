<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type as DBType;

trait MigrationHelper
{
    public function registerMigrationHelpers()
    {
        $this->fixJsonColumns();

        //DB doctrine fix for enum columns
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /*
     * Fix json columns in doctrine dbal
     */
    protected function fixJsonColumns()
    {
        //Add json support
        if ( ! DBType::hasType('json') )
        {
            DBType::addType('json', \Doctrine\DBAL\Types\JsonArrayType::class);
            DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'string');
        }
    }
}