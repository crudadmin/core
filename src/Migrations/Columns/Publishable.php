<?php

namespace Admin\Core\Migrations\Columns;

use Illuminate\Support\Facades\DB;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Publishable extends Column
{
    public $column = 'published_at';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->getProperty('publishable');
    }

    /**
     * Register static column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        //Add Sluggable column support
        if ($columnExists) {
            return;
        }

        return $table->timestamp($this->column)->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
    }
}
