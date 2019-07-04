<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class CreatedAt extends Column
{
    public $column = 'created_at';

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

        return $table->timestamp($this->column)->nullable();
    }
}