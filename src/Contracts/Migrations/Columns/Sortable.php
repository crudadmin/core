<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Sortable extends MigrationColumn
{
    public $column = '_order';

    /**
     * Register static column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        //Check if is enabled sortable support and column does not exists
        if ( ! $model->isSortable() || $columnExists )
            return;

        //If column does not exists in existing table, then regenerate order position
        if ( $update === true && !$columnExists )
            $this->setOrderPosition($model);

        return $table->integer($this->column)->unsigned();;
    }

    //Resave all rows in model for updating slug if needed
    protected function setOrderPosition($model)
    {
        $this->registerAfterMigration($model, function() use ($model) {
            $i = 0;

            foreach ($model->get() as $row)
            {
                $row->_order = $i++;
                $row->save();
            }
        });
    }
}