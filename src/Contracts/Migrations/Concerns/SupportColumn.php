<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use AdminCore;
use Admin\Core\Contracts\Migrations\MigrationColumn;
use Admin\Core\Contracts\Migrations\Columns;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

trait SupportColumn
{
    /*
     * Registered column types
     */
    protected $columns = [
        Columns\Imaginary::class,
        Columns\BelongsTo::class,
        Columns\BelongsToMany::class,
        Columns\Json::class,
        Columns\StringColumn::class,
        Columns\Text::class,
        Columns\LongText::class,
        Columns\Integer::class,
        Columns\Decimal::class,
        Columns\DateTime::class,
        Columns\Checkbox::class,
    ];

    /*
     * Registred statoc column types
     */
    protected $staticColumns = [
        Columns\Sluggable::class,
        Columns\Localization::class,
        Columns\Sortable::class,
        Columns\Publishable::class,
        Columns\CreatedAt::class,
        Columns\UpdatedAt::class,
        Columns\DeletedAt::class,
    ];

    /**
     * Return columns set
     * @return array
     */
    private function getColumns($columnType = null)
    {
        $columnType = $columnType ?: 'columns';

        $columns = $this->{$columnType};

        //We can mutate given columns by reference variable $columns
        AdminCore::fire('migrations.'.$columnType, [&$columns, $this]);

        return $columns;
    }

    /**
     * Returns loaded column class
     * @param  string/object $class
     * @return MigrationColumn
     */
    public function getColumnClass($columnClass)
    {
        if ( is_string($columnClass) )
            $columnClass = new $columnClass;

        //Set class input and output for interaction support
        $columnClass->setInput($this->input);
        $columnClass->setOutput($this->output);

        return $columnClass;
    }

    /**
     * Run column action if exists
     * @param  Column $columnClass
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function runColumnAction($columnClass, $method, $params)
    {
        $columnClass = $this->getColumnClass($columnClass);

        if ( method_exists($columnClass, $method) )
            return $columnClass->{$method}(...$params);

        return null;
    }

    /**
     * Register all static columns
     * @return void
     */
    protected function registerStaticColumns($table, $model, $updating = false)
    {
        foreach ($this->getColumns('staticColumns') as $columnClass)
        {
            $columnClass = $this->getColumnClass($columnClass);

            //If column has not been found, continue
            if ( ! method_exists($columnClass, 'registerStaticColumn') )
                continue;

            //Check if column does exists
            $columnExists = ($updating === false)
                            ? false
                            : $model->getSchema()->hasColumn($model->getTable(), $columnClass->column);

            //Get column response
            $column = $columnClass->registerStaticColumn($table, $model, $updating, $columnExists);

            //If column has not been registred
            if ( !$column || $column === true )
                continue;

            //If static column has been found, and does not exists in db
            if ( ! $columnExists )
                $this->line('<comment>+ Added column:</comment> '.$columnClass->column);
            else
                $column->change();
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
        $column = null;

        foreach ($this->getColumns('columns') as $columnClass)
        {
            $columnClass = $this->getColumnClass($columnClass);

            //If column has been found, skip all other classes
            if ( $column = $this->runColumnAction($columnClass, 'registerColumn', [$table, $model, $key, $updating]) ) {
                break;
            }
        }

        //Unknown column type
        if ( !$column )
            $this->line('<comment>+ Unknown field type</comment> <error>'.$model->getFieldType($key).'</error> <comment>in field</comment> <error>'.$key.'</error>');

        //If column has not been found, or we want skip column registration
        if ( !$column || $column === true )
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
     * @param  ColumnDefinition $column
     * @return void
     */
    private function setDefault(AdminModel $model, string $key, ColumnDefinition $column, MigrationColumn $columnClass)
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