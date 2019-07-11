<?php

namespace Admin\Core\Migrations\Columns;

use Admin\Core\Migrations\Concerns\HasIndex;
use Admin\Core\Migrations\Concerns\MigrationDefinition;
use Admin\Core\Migrations\Concerns\MigrationEvents;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

abstract class Column extends MigrationDefinition
{
    use HasIndex,
        MigrationEvents;

    /*
     * Column name
     */
    protected $column = null;

    /*
     * Get column name
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    abstract public function isEnabled(AdminModel $model);

    /**
     * Register static column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    abstract public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null);
}
