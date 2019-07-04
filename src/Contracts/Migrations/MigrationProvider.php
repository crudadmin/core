<?php

namespace Admin\Core\Contracts\Migrations;

use AdminCore;
use Admin\Core\Contracts\Migrations\Columns;
use Admin\Core\Contracts\Migrations\Concerns\MigrationDefinition;
use Admin\Core\Contracts\Migrations\Types;
use Admin\Core\Eloquent\AdminModel;

class MigrationProvider extends MigrationDefinition
{
    /*
     * Registered column types
     */
    public $types = [
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
     * Registred static column types
     */
    public $staticColumns = [
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
        AdminCore::fire('migrations.column.types', [&$types]);

        return $types;
    }

    /**
     * Get static column
     */
    public function getStaticColumns()
    {
        $columns = $this->staticColumns;

        //We can mutate given columns by reference variable $columns
        AdminCore::fire('migrations.column.static', [&$columns]);

        return $columns;
    }

    /**
     * Returns loaded column class
     * @param  string/object $class
     * @return MigrationDefinition
     */
    public function bootColumnClass($columnClass)
    {
        if ( is_string($columnClass) )
            $columnClass = new $columnClass;

        //Set class input and output for interaction support
        $columnClass->setCommand($this->getCommand());

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
            $columnClass = $this->bootColumnClass($columnClass);

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
            $columnClass = $this->bootColumnClass($columnClass);

            //Check if given column is enabled
            if ( $columnClass->isEnabled($model, $key) === true )
                return $columnClass;
        }

        return null;
    }
}