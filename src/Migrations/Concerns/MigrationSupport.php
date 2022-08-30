<?php

namespace Admin\Core\Migrations\Concerns;

use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type as DBType;
use Doctrine\DBAL\Types\JsonArrayType;
use Illuminate\Database\DBAL\TimestampType;

trait MigrationSupport
{
    public function registerMigrationSupport()
    {
        $this->fixEnumType();

        $this->fixJsonType();
        $this->fixTimestamp();
    }

    private function fixTimestamp()
    {
        DBType::addType('timestamp', TimestampType::class);
    }

    /**
     * DB doctrine fix for enum columns.
     */
    private function fixEnumType()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /*
     * Fix json columns in doctrine dbal
     */
    protected function fixJsonType()
    {
        //Add json support
        if (! DBType::hasType('json')) {
            DBType::addType('json', JsonArrayType::class);
            DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'string');
        }
    }
}
