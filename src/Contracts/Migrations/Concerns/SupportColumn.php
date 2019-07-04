<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use AdminCore;
use Admin\Core\Contracts\Migrations\Columns;
use Admin\Core\Contracts\Migrations\Types;
use Admin\Core\Contracts\Migrations\Types\Type;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

trait SupportColumn
{
    /*
     * Registered column types
     */
    protected $types = [
        Types\ImaginaryType::class,
        Types\BelongsToType::class,
        Types\BelongsToManyType::class,
        Types\JsonType::class,
        Types\StringType::class,
        Types\TextType::class,
        Types\LongTextType::class,
        Types\IntegerType::class,
        Types\DecimalType::class,
        Types\DateTimeType::class,
        Types\CheckboxType::class,
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
     * Get column types
     */
    public function getColumnTypes()
    {
        $types = $this->types;

        //We can mutate given types by reference variable $types
        AdminCore::fire('migrations.column.types', [&$types, $this]);

        return $types;
    }

    /**
     * Get static column
     */
    public function getStaticColumns()
    {
        $columns = $this->staticColumns;

        //We can mutate given columns by reference variable $columns
        AdminCore::fire('migrations.column.static', [&$columns, $this]);

        return $columns;
    }

    /**
     * Returns loaded column class
     * @param  string/object $class
     * @return MigrationDefinition
     */
    public function getColumnClass($columnClass)
    {
        if ( is_string($columnClass) )
            $columnClass = new $columnClass;

        //Set class input and output for interaction support
        $columnClass->setCommand($this);

        return $columnClass;
    }

    /**
     * Returns enabled static fields for each model
     * @param  AdminModel $model
     * @return array
     */
    public function getEnabledStaticFields(AdminModel $model)
    {
        $classes = [];

        foreach ($this->getStaticColumns() as $columnClass)
        {
            $columnClass = $this->getColumnClass($columnClass);

            //Check if given column is enabled
            if ( $columnClass->isEnabled($model) === true )
                $classes[] = $columnClass;
        }

        return $classes;
    }

    /**
     * Returns enabled column type of given field
     * @param  AdminModel $model
     * @param  string     $key
     * @return Type
     */
    public function getColumnType(AdminModel $model, string $key)
    {
        $classes = [];

        foreach ($this->getColumnTypes() as $columnClass)
        {
            $columnClass = $this->getColumnClass($columnClass);

            //Check if given column is enabled
            if ( $columnClass->isEnabled($model, $key) === true )
                return $columnClass;
        }

        return null;
    }

    /**
     * Register all static columns
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool|boolean $updating
     * @return void
     */
    protected function registerStaticColumns(Blueprint $table, AdminModel $model, bool $updating = false)
    {
        foreach ($this->getEnabledStaticFields($model) as $columnClass)
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
        //Unknown column type
        if ( !($columnClass = $this->getColumnType($model, $key)) )
            $this->line('<comment>+ Unknown field type</comment> <error>'.$model->getFieldType($key).'</error> <comment>in field</comment> <error>'.$key.'</error>');

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