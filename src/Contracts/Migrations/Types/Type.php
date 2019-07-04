<?php

namespace Admin\Core\Contracts\Migrations\Types;

use Admin\Core\Contracts\Migrations\Concerns\HasIndex;
use Admin\Core\Contracts\Migrations\Concerns\MigrationDefinition;
use Admin\Core\Contracts\Migrations\Concerns\MigrationEvents;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

abstract class Type extends MigrationDefinition
{
    use HasIndex,
        MigrationEvents;

    /**
     * Register column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    abstract public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update);
}