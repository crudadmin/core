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
    ];

    /**
     * Return columns set
     * @return array
     */
    private function getColumns($columnType = null)
    {
        $columns = $this->{$columnType ?: 'columns'};

        //We can mutate given columns by reference variable $columns
        AdminCore::fire('migrations.columns', [&$columns, $this]);

        return $columns;
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
        if ( method_exists($columnClass, $method) )
            return $columnClass->{$method}(...$params);

        return null;
    }

    /**
     * Run column action and send response into closure if is available
     * @param  string  $method
     * @param  array   $params
     * @param  string  $columnType
     * @param  boolean $updating
     * @param  closure $closure
     * @return void
     */
    public function runFieldAction(string $method, array $params, $closure = null, $columnType = null, $model, $key = null, $updating = false)
    {
        $columns = $this->getColumns($columnType);

        foreach ($columns as $class)
        {
            $columnClass = new $class;

            if ( !method_exists($columnClass, $method) )
                continue;

            //Set class input and output for interaction support
            $columnClass->setInput($this->input);
            $columnClass->setOutput($this->output);

            $columnExists = $updating === false ? false
                            : $model->getSchema()->hasColumn($model->getTable(), $key ?: $columnClass->column);

            //Add column exists parameter
            $params[] = $columnExists;

            $response = $columnClass->{$method}(...$params);

            //If is defined closure, then response will be send into this closure as parameter
            if ( $closure && call_user_func_array($closure, [$response, $columnClass, $columnExists]) === false )
                break;
        }
    }

    /**
     * Register all static columns
     * @return void
     */
    protected function registerStaticColumns($table, $model, $updating = false)
    {
        //Registred column type
        $this->runFieldAction(
            'registerStaticColumn',
            [$table, $model, $updating],
            function($response, $class, $columnExists) use (&$column, &$columnClass, $updating, $model) {
                //If column has not been set
                if ( ! $response )
                    return;

                //If static column has been found, and does not exists in db
                if ( ! $columnExists )
                    $this->line('<comment>+ Added column:</comment> '.$class->column);
                else
                    $response->change();
            },
            'staticColumns',
            $model,
            null,
            $updating
        );
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
        //Get column class
        $columnClass = null;

        //Registred column type
        $this->runFieldAction(
            'registerColumn',
            [$table, $model, $key, $updating],
            function($response, $class) use (&$column, &$columnClass) {
                $columnClass = $class;

                //If column has been found, then stop all other registred classes
                if ( $column = $response )
                    return false;
            },
            'columns',
            $model,
            $key,
            $updating
        );

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