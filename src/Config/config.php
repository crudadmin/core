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
        'date' => 'date_format_multiple:d.m.Y,Y-m-d,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP,Y-m-d\TH:i:s.vP,Y-m-d\TH:i:s.v\Z,Y-m-d\TH:i:s|nullable',
        'datetime' => 'date_format_multiple:d.m.Y H:i,Y-m-d H:i,Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP,Y-m-d\TH:i:s.vP,Y-m-d\TH:i:s.v\Z,Y-m-d\TH:i:s|nullable',
        'timestamp' => 'date_format_multiple:d.m.Y H:i,Y-m-d H:i,Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP,Y-m-d\TH:i:s.vP,Y-m-d\TH:i:s.v\Z,Y-m-d\TH:i:s|nullable',
        'time' => 'date_format_multiple:H:i,Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:sP,Y-m-d\TH:i:s.vP,Y-m-d\TH:i:s.v\Z,Y-m-d\TH:i:s|nullable',
    ],

    'modules' => [
        'App\Admin\Modules' => app_path('Admin/Modules'),
    ],

    'file' => [
        'exists_cache_days' => 31,
        'exists_cache' => false,
        'redirect_after_resize' => true,
    ],
];
