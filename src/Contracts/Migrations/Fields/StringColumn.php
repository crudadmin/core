<?php

namespace Admin\Core\Contracts\Migrations\Fields;

use Admin\Core\Contracts\Migrations\Fields\Field;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class StringColumn extends Field
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
        if ( $model->isFieldType($key, ['string', 'password', 'radio', 'file', 'select']) )
        {
            return $table->string($key, $model->getFieldLength($key));
        }
    }
}