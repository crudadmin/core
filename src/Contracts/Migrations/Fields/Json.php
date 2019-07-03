<?php

namespace Admin\Core\Contracts\Migrations\Fields;

use Admin\Core\Contracts\Migrations\Concerns\SupportJson;
use Admin\Core\Contracts\Migrations\Fields\Field;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Json extends Field
{
    use SupportJson;

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
        if ( $model->isFieldType($key, ['json']) || $model->hasFieldParam($key, ['locale', 'multiple']) )
        {
            return $this->setJsonColumn($table, $key, $model, $update, $model->hasFieldParam($key, ['locale']));
        }
    }
}