<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;
use AdminCore;

trait MigrationEvents
{
    /**
     * Register event migration
     * @param  object $model
     * @param  string $name
     * @param  $callback
     * @return void
     */
    public function migrationEvent($model, $name, $callback)
    {
        $table = $model->getTable();

        AdminCore::event('migrations.'.$table.'.'.$name, $callback);
    }

    /*
     * Run all migrations saved in buffer
     */
    public function fireMigrationEvents($model, $name)
    {
        $modelTable = $model->getTable();

        $model->getSchema()->table($modelTable, function (Blueprint $table) use ($modelTable, $name) {
            AdminCore::fire('migrations.'.$modelTable.'.'.$name, [$table]);
        });
    }

    /**
     * Register event after specific migration
     * @param  object/string $model
     * @param  $callback
     * @return void
     */
    public function registerAfterMigration($model, $callback)
    {
        $this->migrationEvent($model, 'fire_after_migration', $callback);
    }

    /**
     * Register event what will be fired when all migrations will be done
     * @param  object/string $model
     * @param  $callback
     * @return void
     */
    public function registerAfterAllMigrations($model, $callback)
    {
        $this->migrationEvent($model, 'fire_after_all', $callback);
    }

    /*
     * If model method does exists, then run method
     */
    public function fireModelEvent($model, $method)
    {
        //Checks if model has some extre migrations on create
        if ( method_exists($model, $method) )
        {
            $schema = $model->getSchema();

            $schema->table($model->getTable(), function (Blueprint $table) use ($model, $method, $schema) {
                $model->{$method}($table, $schema, $this);
            });
        }
    }
}