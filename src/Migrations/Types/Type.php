<?php

namespace Admin\Core\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Concerns\HasIndex;
use Admin\Core\Migrations\Concerns\MigrationDefinition;
use Admin\Core\Migrations\Concerns\MigrationEvents;
use Admin\Core\Migrations\Concerns\SchemeSupport;
use Admin\Core\Migrations\Concerns\SupportColumn;
use Illuminate\Database\Schema\Blueprint;

abstract class Type extends MigrationDefinition
{
    use HasIndex,
        MigrationEvents,
        SchemeSupport;

    /*
     * This column does represent existing column in database
     */
    protected $hasColumn = true;

    /*
     * Index postfix
     */
    protected $indexType = 'index';

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

    /**
     * Returns index type
     *
     * @return  string
     */
    public function getIndexType()
    {
        return $this->indexType;
    }
}
