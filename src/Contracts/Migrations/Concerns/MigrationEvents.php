<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use Illuminate\Database\Schema\Blueprint;

trait MigrationEvents
{
    /**
     * Register event after specific migration
     * @param  object $model
     * @param  string $name
     * @param  $callback
     * @return void
     */
    public function event($model, $name, $callback)
    {
        $table = $model->getTable();

        $this->push($table, $callback, $name);
    }

    /**
     * Register event after specific migration
     * @param  object/string $model
     * @param  $callback
     * @return void
     */
    public function registerAfterMigration($model, $callback)
    {
        $this->event($model, 'fire_after_migration', $callback);
    }

    /**
     * Register event what will be fired when all migrations will be done
     * @param  object/string $model
     * @param  $callback
     * @return void
     */
    public function registerAfterAllMigrations($model, $callback)
    {
        $this->event($model, 'fire_after_all', $callback);
    }

    /*
     * Run all migrations saved in buffer
     */
    public function fireMigrationEvents($model, $type)
    {
        $table = $model->getTable();

        $events = $this->get($table);

        if ( ! array_key_exists($table, $events) )
            return;

        foreach ($events[ $table ] as $function)
        {
            $model->getSchema()->table($table, function (Blueprint $table) use ($function) {
                $function($table);
            });
        }
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