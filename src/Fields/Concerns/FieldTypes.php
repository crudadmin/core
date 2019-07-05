<?php

namespace Admin\Core\Fields\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Types;

trait FieldTypes
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

    /**
     * Get column types
     */
    public function getColumnTypes()
    {
        return $this->types;
    }


    /**
     * Add column type
     * @param string $class
     */
    public function addColumnType($class)
    {
        $this->types = $class;
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
?>