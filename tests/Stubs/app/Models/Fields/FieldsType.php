<?php

namespace Admin\Core\Tests\App\Models\Fields;

use Admin\Core\Eloquent\AdminModel;

class FieldsType extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2019-05-03 12:02:04';

    /*
     * Template name
     */
    protected $name = 'Fields types';

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
            'string' => 'name:my string field|type:string|title:this is my field description|required',
            'text' => 'name:my text field|type:text|required',
            'longtext' => 'name:my longtext field|type:longtext|required',
            'integer' => 'name:my integer field|type:integer|required',
            'decimal' => 'name:my decimal field|type:decimal|required',
            'file' => 'name:my file field|type:file|required',
            'date' => 'name:my date field|type:date|required',
            'datetime' => 'name:my datetime field|type:datetime|required',
            'time' => 'name:my time field|type:time|required',
            'checkbox' => 'name:my checkbox field|type:checkbox',
        ];
    }
}