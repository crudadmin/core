<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\Concerns\SupportJson;
use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Json extends MigrationColumn
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
    public function registerColumn(Blueprint $table, AdminModel $model, string $key, bool $update)
    {
        if ( $model->isFieldType($key, ['json']) || $model->hasFieldParam($key, ['locale', 'multiple']) )
        {
            return $this->setJsonColumn($table, $key, $model, $update, $model->hasFieldParam($key, ['locale']));
        }
    }
}