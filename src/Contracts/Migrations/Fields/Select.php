<?php

namespace Admin\Core\Contracts\Migrations\Fields;

use Admin\Core\Contracts\Migrations\Fields\Field;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Select extends Field
{
    /**
     * Register column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    public function register(Blueprint $table, AdminModel $model, string $key, bool $update)
    {
        //Timestamp columns
        if ( $model->isFieldType($key, ['date', 'datetime', 'time']) )
        {
            //Check for correct values
            if ( $update === true )
            {
                $type = $model->getConnection()->getDoctrineColumn($model->getTable(), $key)->getType()->getName();

                //If previoius column has not been datetime and has some value
                if (
                    ! in_array($type, ['date', 'datetime', 'time'])
                    && $this->confirm('You are updating '.$key.' column from non-date "'.$type.'" type to datetime type. Would you like to update this non-date values to null values?')
                ){
                    $model->getConnection()->table($model->getTable())->update([ $key => null ]);
                }
            }

            $column = $table->{$model->getFieldType($key)}($key)->nullable();

            return $column;
        }
    }
}