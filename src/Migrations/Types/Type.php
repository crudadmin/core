<?php

namespace Admin\Core\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Admin\Core\Migrations\Concerns\HasIndex;
use Admin\Core\Migrations\Concerns\MigrationEvents;
use Admin\Core\Migrations\Concerns\MigrationDefinition;

abstract class Type extends MigrationDefinition
{
    use HasIndex,
        MigrationEvents;

    /*
     * This column does represent existing column in database
     */
    protected $hasColumn = true;

    /*
     * Returns if column is represented with existing column in databasse
     */
    public function hasColumn()
    {
        return $this->hasColumn;
    }

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    abstract public function isEnabled(AdminModel $model, string $key);

    /**
     * Register column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    abstract public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update);
}
