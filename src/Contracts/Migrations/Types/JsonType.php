<?php

namespace Admin\Core\Contracts\Migrations\Types;

use Admin\Core\Contracts\Migrations\Concerns\SupportJson;
use Admin\Core\Contracts\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class JsonType extends Type
{
    use SupportJson;

    /**
     * Check if can apply given column
     * @param  AdminModel  $model
     * @param  string      $key
     * @return boolean
     */
    public function isEnabled(AdminModel $model, string $key)
    {
        return $model->isFieldType($key, ['json']) || $model->hasFieldParam($key, ['locale', 'multiple']);
    }

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
        return $this->setJsonColumn($table, $key, $model, $update, $model->hasFieldParam($key, ['locale']));
    }
}