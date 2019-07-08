<?php

namespace Admin\Core\Migrations\Concerns;

use Fields;
use AdminCore;
use Admin\Core\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

trait SupportColumn
{
    /**
     * Register all static columns
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool|boolean $updating
     * @return void
     */
    protected function registerStaticColumns(Blueprint $table, AdminModel $model, bool $updating = false)
    {
        foreach ($enabled = Fields::getEnabledStaticFields($model) as $columnClass)
        {
            //Check if column does exists
            $columnExists = ($updating === false)
                            ? false
                            : $model->getSchema()->hasColumn($model->getTable(), $columnClass->getColumn());

            //Get column response
            $column = $columnClass->registerStaticColumn($table, $model, $updating, $columnExists);

            //If column has not been registred
            if ( !$column || $column === true )
                continue;

            //If column does exists
            if ( $columnExists ) {
                $column->change();
            }

            //If static column has been found, and does not exists in db and is updating
            else if ( $updating ) {
                $this->setStaticColumnPosition($table, $enabled, $column);

                $this->line('<comment>+ Added column:</comment> '.$columnClass->column);
            }
        }
    }

    /**
     * Set all column types by registred classes
     * @param Blueprint     $table
     * @param AdminModel    $model
     * @param string        $key
     * @param bool          $updating
     */
    protected function registerColumn(Blueprint $table, AdminModel $model, $key, $updating = false)
    {
        //Unknown column type
        if ( !($columnClass = Fields::getColumnType($model, $key)) ) {
            $this->line('<comment>+ Unknown field type</comment> <error>'.$model->getFieldType($key).'</error> <comment>in field</comment> <error>'.$key.'</error>');
            return;
        }

        //Get column response
        $column = $columnClass->registerColumn($table, $model, $key, $updating);

        //If column has not been found, or we want skip column registration
        if ( !$column || $column === true || $columnClass->hasColumn() == false )
            return;

        //Set nullable column
        $this->setNullable($model, $key, $column);

        //If field is index
        $this->setIndex($model, $key, $column);

        //Set default value of field
        $this->setDefault($model, $key, $column, $columnClass);

        return $column;
    }

    /**
     * Set nullable column
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  ColumnDefinition $column
     * @return void
     */
    private function setNullable(AdminModel $model, string $key, ColumnDefinition $column)
    {
        if ( ! $model->hasFieldParam($key, 'required') )
            $column->nullable();
    }

    /**
     * Set column index
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  ColumnDefinition $column
     * @return void
     */
    private function setIndex(AdminModel $model, string $key, ColumnDefinition $column)
    {
        if ( ! $model->hasFieldParam($key, 'index') )
            return;

        //If index does exist already
        if (
            !$model->getSchema()->hasTable( $model->getTable() ) ||
            !$this->hasIndex($model, $key, 'index')
        ) {
            $column->index();
        }
    }

    /**
     * Set default column value
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  Type             $column
     * @return void
     */
    private function setDefault(AdminModel $model, string $key, ColumnDefinition $column, Type $columnClass)
    {
        //If field does not have default value
        if ( ! $model->hasFieldParam($key, 'default') ) {
            $column->default(NULL);
        }

        //If column has own set default setter
        if ( method_exists($columnClass, 'setDefault') ) {
            $columnClass->setDefault($column, $model, $key);
            return;
        }

        //Set value by parameter
        $default = $model->getFieldParam($key, 'default');

        $column->default($default);
    }
}