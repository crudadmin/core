<?php

namespace Admin\Core\Tests\App\Models\Articles;

use Admin\Core\Eloquent\AdminModel;

class ArticlesComment extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-04 12:10:15';

    protected $belongsToModel = Article::class;

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
            'name' => 'name:Comment|type:string',
        ];
    }
}
