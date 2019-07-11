<?php

namespace Admin\Core\Migrations\Types;

use AdminCore;
use Admin\Core\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

class BelongsToType extends Type
{
    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->hasFieldParam($key, 'belongsTo');
    }

    /**
     * Register column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update)
    {
        $properties = $model->getRelationProperty($key, 'belongsTo');

        $parent = AdminCore::getModelByTable($properties[0]);

        //If table in belongsTo relation does not exists
        if (! $parent) {
            $this->getCommand()->line('<error>Table '.$properties[0].' does not exists.</error>');
            die;
        }

        //Skip adding new foreign key if exists from belongsToModel property
        if ($this->isForeignInBelongsToModel($table, $key)) {
            return true;
        }

        //If foreign key in table exists
        $keyExists = 0;

        //Check if actual table and key exists
        if ($tableExists = $model->getSchema()->hasTable($model->getTable())) {
            $keyExists = $this->hasIndex($model, $key);
        }

        //If table has not foreign column
        if ($keyExists == 0) {
            //Checks if table has already inserted rows which won't allow insert foreign key without NULL value
            if ($tableExists === true && $model->count() > 0 && $model->hasFieldParam($key, 'required', true)) {
                $this->checkForReferenceTable($model, $key, $properties[0]);
            }

            $this->registerAfterAllMigrations($model, function ($table) use ($key, $properties, $model, $parent) {
                if ($parent->getSchema()->hasTable($parent->getTable())) {
                    $table->foreign($key)->references($properties[2])->on($properties[0]);
                }
            });
        }

        return $table->integer($key)->unsigned();
    }

    /**
     * Set default value.
     * @param ColumnDefinition $column
     * @param AdminModel       $model
     * @param string           $key
     */
    public function setDefault(ColumnDefinition $column, AdminModel $model, string $key)
    {
        $column->default(null);
    }

    /**
     * Check if column is also foreign key from belongsToModel property.
     * @param  string  $table
     * @param  string  $key
     * @return bool
     */
    private function isForeignInBelongsToModel($table, $key)
    {
        $has_column = array_filter($table->getColumns(), function ($column) use ($key) {
            return $column->name == $key;
        });

        //Check if relationship column has been already added from belongsToModelProperty
        return count($has_column) > 0;
    }
}
