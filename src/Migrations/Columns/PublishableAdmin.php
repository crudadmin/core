<?php

namespace Admin\Core\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Concerns\SupportJson;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class PublishableAdmin extends Column
{
    use SupportJson;

    public $column = 'published_state';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->getProperty('publishableState');
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

        return $this->setJsonColumn($table, $this->column, $model, $update);
    }
}
