<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class Publishable extends MigrationColumn
{
    public $column = 'published_at';

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
        if ( ! $model->getProperty('publishable') || $columnExists )
            return;

        return $table->timestamp($this->column)->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );
    }
}