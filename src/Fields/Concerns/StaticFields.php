<?php

namespace Admin\Core\Fields\Concerns;

use Fields;
use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Columns;

trait StaticFields
{
    /*
     * Registred static column types
     */
    public $staticColumns = [
        Columns\Sluggable::class,
        Columns\Publishable::class,
        Columns\CreatedAt::class,
        Columns\UpdatedAt::class,
        Columns\DeletedAt::class,
    ];

    /**
     * Get static column
     */
    public function getStaticColumns()
    {
        return $this->staticColumns;
    }

    /**
     * Add static column (one or multiple at once)
     * @param string/array $class
     */
    public function addStaticColumn($classes)
    {
        foreach (array_reverse(array_wrap($classes)) as $class)
        {
            if ( in_array($class, $this->staticColumns) )
                continue;

            //Add into first position in array
            //because we want added columns behind of all static columns in database
            array_unshift($this->staticColumns, $class);
        }
    }

    /**
     * Returns enabled static fields for each model
     * @param  AdminModel $model
     * @return array
     */
    public function getEnabledStaticFields(AdminModel $model)
    {
        return Fields::cache('models.'.$model->getTable().'.static_columns.', function() use($model) {
            $classes = [];

            foreach ($this->getStaticColumns() as $columnClass)
            {
                $columnClass = $this->bootColumnClass($columnClass);

                //Check if given column is enabled
                if ( $columnClass->isEnabled($model) === true )
                    $classes[] = $columnClass;
            }

            return $classes;
        });
    }
}
?>