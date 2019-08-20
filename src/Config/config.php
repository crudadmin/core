<?php

return [
    /*
     * From which directories CrudAdmin should load Admin Modules
     */
    'models' => [
        'App' => app_path(),
        'App\Model' => app_path('Model'),
        'App\Eloquent' => app_path('Eloquent'),
    ],

    /*
     * Custom rules aliases
     */
    'custom_rules' => [
        'image' => 'type:file|image|max:5120',
        'belongsToMany' => 'array',
        'unsigned' => 'min:0',
    ],

    /*
     * Global rules on fields type
     */
    'global_rules' => [
        'string' => 'max:255',
        'integer' => 'integer|max:4294967295',
        'decimal' => 'numeric',
        'file' => 'max:10240|file|nullable',
        'checkbox' => 'boolean',
        'date' => 'date_format:d.m.Y|nullable',
        'datetime' => 'date_format:d.m.Y H:i|nullable',
        'time' => 'date_format:H:i|nullable',
    ],
];
