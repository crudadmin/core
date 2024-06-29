<?php

namespace Admin\Core\Migrations\Types;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Types\Type;
use Illuminate\Database\Schema\Blueprint;

class EnumType extends Type
{
    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, ['select']);
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
        if ( $model->hasFieldParam($key, 'enum') ) {
            return $table->enum($key, array_keys($model->getField($key)['options']));
        }

        return $table->string($key, $model->getFieldLength($key));
    }
}
