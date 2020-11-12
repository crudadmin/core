<?php

namespace Admin\Core\Migrations\Concerns;

use Fields;
use Illuminate\Support\Facades\DB;
use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Types\Type;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema;
use Admin;

trait SupportColumn
{
    public function getTableColumn(AdminModel $model, $key)
    {
        $columns = Admin::cache('migrations.columns.doctrine.'.$model->getTable(), function() use ($model) {
            return $model->getConnection()->getDoctrineSchemaManager()->listTableColumns(
                $model->getTable()
            );
        });

        return @$columns[$key];
    }

    /**
     * Register all static columns.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool|bool $updating
     * @return void
     */
    protected function registerStaticColumns(Blueprint $table, AdminModel $model, bool $updating = false)
    {
        foreach ($enabled = Fields::getEnabledStaticFields($model) as $columnClass) {
            //Check if column does exists
            $columnExists = ($updating === false)
                            ? false
                            : $model->getSchema()->hasColumn($model->getTable(), $columnClass->getColumn());

            //Get column response
            $column = $columnClass->registerStaticColumn($table, $model, $updating, $columnExists);

            //If column has not been registred
            if (! $column || $column === true) {
                continue;
            }

            //If column does exists
            if ($columnExists) {
                $column->change();
            }

            //If static column has been found, and does not exists in db and is updating
            elseif ($updating) {
                $this->setStaticColumnPosition($table, $enabled, $column);

                $this->line('<comment>+ Added column:</comment> '.$columnClass->column);
            }
        }
    }

    /**
     * Set all column types by registred classes.
     * @param Blueprint     $table
     * @param AdminModel    $model
     * @param string        $key
     * @param bool          $updating
     */
    protected function registerColumn(Blueprint $table, AdminModel $model, $key, $updating = false)
    {
        //Unknown column type
        if (! ($columnClass = Fields::getColumnType($model, $key))) {
            $this->line('<comment>+ Unknown field type</comment> <error>'.$model->getFieldType($key).'</error> <comment>in field</comment> <error>'.$key.'</error>');

            return;
        }

        //Get column response
        $column = $columnClass->setCommand($this->getCommand())
                              ->registerColumn($table, $model, $key, $updating);

        //If column has not been found, or we want skip column registration
        if (! $column || $column === true || $columnClass->hasColumn() == false) {
            return;
        }

        //Set nullable column
        $this->setNullable($model, $key, $column, $columnClass);

        //If field is index
        $this->setIndex($model, $key, $column);

        //Set default value of field
        $this->setDefault($model, $key, $column, $columnClass);

        return $column;
    }

    /**
     * Determine if columns is nullable or not
     *
     * @param  AdminModel  $model
     * @param  string  $key
     * @return  bool
     */
    public function isNullable($model, $key)
    {
        return $model->hasFieldParam($key, ['required'], true) === false
                || $model->hasFieldParam($key, 'null', true) === true;
    }

    /**
     * Set nullable column.
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  mixed $column
     * @param  Type $columnClass
     * @return void
     */
    public function setNullable(AdminModel $model, string $key, $column, Type $columnClass = null)
    {
        //If column has own set default setter
        if ($columnClass && method_exists($columnClass, 'setNullable')) {
            return $columnClass->setNullable($column, $model, $key, $this);
        }

        if ($this->isNullable($model, $key)) {
            $column->nullable();
        } else {
            $column->nullable(false);
        }
    }

    /**
     * Set column index.
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  mixed $column
     * @return void
     */
    private function setIndex(AdminModel $model, string $key, $column)
    {
        if (! $model->hasFieldParam($key, 'index', true)) {
            return;
        }

        //If index does exist already
        if (
            ! $model->getSchema()->hasTable($model->getTable()) ||
            ! $this->hasIndex($model, $key, 'index')
        ) {
            $column->index();
        }
    }

    /**
     * Set default column value.
     * @param  AdminModel       $model
     * @param  string           $key
     * @param  Type             $column
     * @return void
     */
    private function setDefault(AdminModel $model, string $key, $column, Type $columnClass)
    {
        //If field does not have default value
        if (! $model->hasFieldParam($key, 'default')) {
            $column->default(null);
        }

        //If column has own set default setter
        if (method_exists($columnClass, 'setDefault')) {
            $columnClass->setDefault($column, $model, $key);
            return;
        }

        //Set value by parameter
        $default = $model->getFieldParam($key, 'default');

        $column->default($default);
    }
}
