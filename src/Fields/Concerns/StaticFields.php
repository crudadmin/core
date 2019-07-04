<?php

namespace Admin\Core\Fields\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Contracts\Migrations\Columns;

trait StaticFields
{
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
     * Get static column
     */
    public function getStaticColumns()
    {
        return $this->staticColumns;
    }

    /**
     * Add column type
     * @param string $class
     */
    public function addStaticColumn($class)
    {
        $this->staticColumns = $class;
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
}
?>