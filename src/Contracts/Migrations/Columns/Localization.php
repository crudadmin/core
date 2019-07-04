<?php

namespace Admin\Core\Contracts\Migrations\Columns;

use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class Localization extends MigrationColumn
{
    public $column = 'language_id';

    /**
     * Register static column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        //Check if is enabled localization support and column does not exists
        if ( ! $model->isEnabledLanguageForeign() || $columnExists )
            return;

        return $this->createLanguageRelationship($table, $model, $update);
    }

    /*
     * Add language_id relationship
     */
    protected function createLanguageRelationship($table, $model, $updating = false)
    {
        $column = $table->integer('language_id')->unsigned()->nullable();

        //If is creating new column in existing table, add column after id
        if ( $updating == true ) {
            $column->after('id');
        }

        $table->foreign('language_id')->references('id')->on('languages');

        return $column;
    }
}