<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

use Admin\Core\Eloquent\AdminModel;
use Localization;
use Cache;

trait SupportSluggable
{
    protected function setSlug($table, $model, $updating = false, $render = true)
    {
        $slugcolumn = $model->getProperty('sluggable');

        if ( ! ($field = $model->getField($slugcolumn)) )
        {
            $this->line('<comment>+ Unknown slug column for</comment> <error>'.$slugcolumn.'</error> <comment>column</comment>');

            return;
        }

        //Set locale slug or normal
        if ( $has_locale = $model->hasFieldParam($slugcolumn, 'locale', true) )
            $column = $this->setJsonColumn($table, 'slug', $model, $updating, true);
        else
            $column = $table->string('slug', $model->getFieldLength($slugcolumn));

        if ( $updating == true )
        {
            $column->after( $slugcolumn );
        }

        //If is creating new table or when slug index is missing
        if ( !$has_locale && ($updating === false || ! $this->hasIndex($model, 'slug', 'index')) )
            $column->index();

        if ( $has_locale && $updating === true && $this->hasIndex($model, 'slug', 'index') )
            $this->dropIndex($model, 'slug', 'index');

        //If is field required
        if( ! $model->hasFieldParam( $slugcolumn , 'required') )
            $column->nullable();

        //If was added column to existing table, then reload sluggs
        if ( $render == true )
        {
            $this->updateSlugs($model);
        }

        return $column;
    }

    //Resave all rows in model for updating slug if needed
    protected function updateSlugs($model)
    {
        $this->registerAfterMigration($model, function() use ($model) {

            //Get empty slugs
            $empty_slugs = $model->withoutGlobalScopes()->where(function($query) use ($model) {
                //If some of localized slug value is empty
                if ( $model->hasLocalizedSlug() )
                {
                    $languages = Localization::getLanguages(true);

                    //Check all available languages slugs
                    foreach ($languages as $key => $lang)
                    {
                        $query->{ $key == 0 ? 'where' : 'orWhere' }(function($query) use($model, $lang) {
                            //If row has defined localized value, but slug is missing
                            $query->whereRaw('JSON_EXTRACT(slug, "$.'.$lang->slug.'") is NULL')
                                  ->whereRaw('JSON_EXTRACT('.$model->getProperty('sluggable').', "$.'.$lang->slug.'") is NOT NULL');
                        });
                    }
                }

                //If simple slug is empty
                else {
                    $query->whereNull('slug')->orWhere('slug', '');
                }

            })->orWhere('slug', null);

            //If has been found some empty slugs
            if ( $empty_slugs->count() > 0 )
            {
                //Re-save models, and regenerate new slugs
                foreach ($empty_slugs->select([$model->getKeyName(), 'slug', $model->getProperty('sluggable')])->get() as $row)
                    $row->save();
            }
        });
    }
}