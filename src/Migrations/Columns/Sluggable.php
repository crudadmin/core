<?php

namespace Admin\Core\Migrations\Columns;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Migrations\Concerns\SupportJson;
use Illuminate\Database\Schema\Blueprint;
use Localization;

class Sluggable extends Column
{
    use SupportJson;

    public $column = 'slug';

    /**
     * Check if can apply given column.
     * @param  AdminModel  $model
     * @return bool
     */
    public function isEnabled(AdminModel $model)
    {
        return $model->getProperty('sluggable') !== null;
    }

    /**
     * Register static column.
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {
        return $this->setSlug($table, $model, $update, $columnExists === false);
    }

    /**
     * Add sluggable column support.
     * @param Blueprint  $table
     * @param AdminModel $model
     * @param bool    $updating
     * @param bool    $reloadSlugs
     */
    protected function setSlug(Blueprint $table, AdminModel $model, $updating = false, $reloadSlugs = true)
    {
        $slugcolumn = $model->getProperty('sluggable');

        if (! ($field = $model->getField($slugcolumn))) {
            $this->getCommand()->line('<comment>+ Unknown slug column for</comment> <error>'.$slugcolumn.'</error> <comment>column</comment>');

            return;
        }

        //Set locale slug or normal
        if ($has_locale = $model->hasFieldParam($slugcolumn, 'locale', true)) {
            $column = $this->setJsonColumn($table, $this->column, $model, $updating, true);
        } else {
            $column = $table->string($this->column, $model->getFieldLength($slugcolumn));
        }

        //If is creating new table or when slug index is missing
        if (! $has_locale && ($updating === false || ! $this->hasIndex($model, $this->column, 'index'))) {
            $column->index();
        }

        if ($has_locale && $updating === true && $this->hasIndex($model, $this->column, 'index')) {
            $this->dropIndex($model, $this->column, 'index');
        }

        //If is field required
        if (! $model->hasFieldParam($slugcolumn, 'required')) {
            $column->nullable();
        }

        //If column has been added into existing table, then regenerate all slugs
        if ($reloadSlugs == true) {
            $this->updateSlugs($model);
        }

        return $column;
    }

    /**
     * Resave all rows in model for updating slug if needed.
     * @param  AdminModel $model
     * @return void
     */
    protected function updateSlugs(AdminModel $model)
    {
        $this->registerAfterMigration($model, function () use ($model) {
            //Get empty slugs
            $empty_slugs = $model->withoutGlobalScopes()->when($model->hasSoftDeletes(), function($query){
                $query->withoutTrashed();
            })->where(function ($query) use ($model) {
                //If some of localized slug value is empty
                if ($model->hasLocalizedSlug()) {
                    $languages = Localization::getLanguages();

                    //Check all available languages slugs
                    foreach ($languages as $key => $lang) {
                        $query->{ $key == 0 ? 'where' : 'orWhere' }(function ($query) use ($model, $lang) {
                            //If row has defined localized value, but slug is missing
                            $query->whereRaw('JSON_EXTRACT('.$this->column.', "$.'.$lang->slug.'") is NULL')
                                  ->whereRaw('JSON_EXTRACT('.$model->getProperty('sluggable').', "$.'.$lang->slug.'") is NOT NULL');
                        });
                    }
                }

                //If simple slug is empty
                else {
                    $query->whereNull($this->column)->orWhere($this->column, '');
                }
            })->orWhere($this->column, null);

            //If has been found some empty slugs
            if ($empty_slugs->count() > 0) {
                //Re-save models, and regenerate new slugs
                foreach ($empty_slugs->select([$model->getKeyName(), $this->column, $model->getProperty('sluggable')])->get() as $row) {
                    $row->save();
                }
            }
        });
    }
}
