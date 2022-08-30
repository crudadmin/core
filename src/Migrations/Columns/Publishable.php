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
        $indexName = $this->getIndexName($model, $this->column, 'index');

        //Add Sluggable column support
        if ($columnExists) {
            //We need check index also on existing columns. Because crudadmin < 3.3 does not use index
            //what is huge performance issue
            if ( $this->hasIndex($model, $this->column, 'index') == false ) {
                $this->addIndex($model, $this->column, 'index');
            }

            return;
        }

        return $table->timestamp($this->column)->nullable()->index($indexName)->default(DB::raw('CURRENT_TIMESTAMP'));
    }
}
