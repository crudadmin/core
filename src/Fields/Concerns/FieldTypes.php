<?php

namespace Admin\Core\Fields\Concerns;

use Fields;
use Admin\Core\Migrations\Types;
use Admin\Core\Eloquent\AdminModel;

trait FieldTypes
{
    /**
     * Registered column types.
     *
     * @var array
     */
    public $types = [
        Types\BelongsToType::class,
        Types\BelongsToManyType::class,
        Types\TextType::class,
        Types\JsonType::class,
        Types\StringType::class,
        Types\LongTextType::class,
        Types\IntegerType::class,
        Types\DecimalType::class,
        Types\GeometryType::class,
        Types\DateTimeType::class,
        Types\TimestampType::class,
        Types\BooleanType::class,
    ];

    /**
     * Get column types.
     *
     * @return array
     */
    public function getColumnTypes()
    {
        return $this->types;
    }

    /**
     * Add column type at the beginning of all types.
     * Sometimes you need apply field with same rules
     * as other fields, but with additional parameter.
     * In this case this function will help you.
     *
     * @param string/array $classes
     * @return void
     */
    public function addColumnTypeBefore($classes)
    {
        $this->addColumnType($classes, true);
    }

    /**
     * Add column type.
     *
     * @param string|array  $classes.
     * @param bool  $before
     * @return void
     */
    public function addColumnType($classes, $before = false)
    {
        $add = [];

        foreach (array_wrap($classes) as $class) {
            //If class does not exists in array
            if (in_array($class, $this->types) === false) {
                $add[] = $class;
            }
        }

        //Add types at the beggining or at the end of array
        $this->types = $before === true ? array_merge(array_reverse($add), $this->types)
                                        : array_merge($this->types, $add);
    }

    /**
     * Returns enabled column type of given field.
     *
     * @param  AdminModel  $model
     * @param  string  $key
     * @return Admin\Core\Migrations\Types\Type|null
     */
    public function getColumnType(AdminModel $model, string $key)
    {
        return $this->cache($this->getModelKey($model) . '.fields.type.'.$key, function() use ($model, $key) {
            $classes = [];

            foreach ($this->getColumnTypes() as $columnClass) {
                $columnClass = $this->bootColumnClass($columnClass);

                //Check if given column is enabled
                if ($columnClass->isEnabled($model, $key) === true) {
                    return $columnClass;
                }
            }
        });
    }
}
