<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class StringColumn extends MigrationColumn
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
        if ( $model->isFieldType($key, ['string', 'password', 'radio', 'file', 'select']) )
        {
            return $table->string($key, $model->getFieldLength($key));
        }
    }
}