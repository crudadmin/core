<?php

namespace Admin\Core\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class DateTimeType extends Type
{
    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, ['date', 'datetime', 'time']);
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
        //Check for correct values
        if ($update === true) {
            $type = $model->getConnection()->getDoctrineColumn($model->getTable(), $key)->getType()->getName();

            //If previoius column has not been datetime and has some value
            if (
                ! in_array($type, ['date', 'datetime', 'time'])
                && $this->getCommand()->confirm('You are updating '.$key.' column from non-date "'.$type.'" type to datetime type. Would you like to update this non-date values to null values?')
            ) {
                $model->getConnection()->table($model->getTable())->update([$key => null]);
            }
        }

        $column = $table->{$model->getFieldType($key)}($key)->nullable();

        return $column;
    }

    /**
     * Set default value.
     * @param mixed $column
     * @param AdminModel       $model
     * @param string           $key
     */
    public function setDefault($column, AdminModel $model, string $key)
    {
        //If default value has not been set
        if (! ($default = $model->getFieldParam($key, 'default'))) {
            return;
        }

        //Set default timestamp
        if (strtoupper($default) == 'CURRENT_TIMESTAMP') {
            $column->useCurrent();
        }
    }
}
