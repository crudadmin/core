<?php

namespace Admin\Core\Fields\Concerns;

use Fields;
use Admin\Core\Migrations\Columns;
use Admin\Core\Eloquent\AdminModel;

trait StaticFields
{
    /**
     * Registred static column types.
     *
     * @var array
     */
    public $staticColumns = [
        Columns\Sluggable::class,
        Columns\Publishable::class,
        Columns\PublishableAdmin::class,
        Columns\CreatedAt::class,
        Columns\UpdatedAt::class,
        Columns\DeletedAt::class,
    ];

    /**
     * Get static column.
     *
     * @return array
     */
    public function getStaticColumns()
    {
        return $this->staticColumns;
    }

    /**
     * Add static column (one or multiple at once).
     *
     * @param  array|string  $classes
     * @return void
     */
    public function addStaticColumn($classes)
    {
        foreach (array_reverse(array_wrap($classes)) as $class) {
            if (in_array($class, $this->staticColumns)) {
                continue;
            }

            //Add into first position in array
            //because we want added columns behind of all static columns in database
            array_unshift($this->staticColumns, $class);
        }
    }

    /**
     * Returns enabled static fields for each model.
     *
     * @param  Admin\Core\Eloquent\AdminModel $model
     * @return array
     */
    public function getEnabledStaticFields(AdminModel $model)
    {
        return $this->cache('models.'.$this->getModelKey($model).'.static_columns.', function () use ($model) {
            $classes = [];

            foreach ($this->getStaticColumns() as $columnClass) {
                $columnClass = $this->bootColumnClass($columnClass);

                //Check if given column is enabled
                if ($columnClass->isEnabled($model) === true) {
                    $classes[] = $columnClass;
                }
            }

            return $classes;
        });
    }
}
