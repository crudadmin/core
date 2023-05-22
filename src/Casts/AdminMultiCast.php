<?php

namespace Admin\Core\Casts;

use Admin\Core\Casts\Concerns\MultiCast;
use Illuminate\Contracts\Database\Eloquent\Castable;

class AdminMultiCast implements Castable
{
    public static function castUsing(array $arguments)
    {
        return new class($arguments, true) extends MultiCast
        {
            //..
        };
    }
}