<?php

namespace Admin\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Fields extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'admin.fields';
    }
}