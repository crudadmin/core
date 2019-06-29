<?php

namespace Admin\Core\Facades;

use Illuminate\Support\Facades\Facade;

class AdminCore extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'admin_core';
    }
}