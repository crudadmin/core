<?php

return [
    /*
     * From which directories CrudAdmin should load Admin Modules
     */
    'models' => [
        app_path() => 'App',
        app_path('/Model/*') => 'App/Model',
        app_path('/Eloquent/*') => 'App/Eloquent',
    ],
];