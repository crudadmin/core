<?php

namespace Admin\Core\Tests\App\Models\Fields;

use Admin\Core\Eloquent\AdminModel;
use Admin\Core\Fields\Group;
use Admin\Tests\App\Models\Articles\Article;

class FieldsRelation extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 14:12:04';

    /*
     * Template name
     */
    protected $name = 'Fields relations';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'article' => 'name:BelongsTo (simple)|belongsTo:articles,name|required',
            'multiple' => 'name:BelongsToMany (simple)|belongsToMany:articles,name|required',
        ];
    }
}
