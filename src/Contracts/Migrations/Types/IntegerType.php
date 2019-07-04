<?php

namespace Admin\Core\Contracts\Migrations\Types;

use Admin\Core\Contracts\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class IntegerType extends Type
{
    /**
     * Register column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  string       $key
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update)
    {
        //Integer columns
        if ( $model->isFieldType($key, 'integer') )
        {
            $column = $table->integer($key);

            //Check if is integer unsigned or not
            if ($model->hasFieldParam($key, 'min') && $model->getFieldParam($key, 'min') >= 0)
                $column->unsigned();

            return $column;
        }
    }
}