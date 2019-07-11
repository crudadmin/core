<?php

namespace Admin\Core\Migrations\Types;

use Admin\Core\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class BooleanType extends Type
{
    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @param  string      $key
     * @return bool
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, ['checkbox', 'boolean']);
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
        $default = $model->hasFieldParam($key, 'default') ? $model->getFieldParam($key, 'default') : 0;

        return $table->boolean($key)->default($default);
    }
}
