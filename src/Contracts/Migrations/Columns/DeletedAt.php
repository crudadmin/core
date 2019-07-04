<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class DeletedAt extends MigrationColumn
{
    public $column = 'deleted_at';

    /**
     * Check if can apply given column
     * @param  AdminModel  $model
     * @return boolean
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->getProperty('timestamps');
    }

    /**
     * Register static column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        //Add Sluggable column support
        if ( $columnExists )
            return;

        return $table->softDeletes($this->column);
    }
}