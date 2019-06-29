<?php

namespace Admin\Core\Facades;

use Illuminate\Support\Facades\Facade;

class DataStore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'admin.store';
    }
}