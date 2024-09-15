<?php

namespace Admin\Core\Casts;

use Admin\Core\Casts\Concerns\AdminCast;
use Admin\Core\Helpers\Storage\AdminFile;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class AdminFileCast implements CastsAttributes, AdminCast
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        return $model->getAdminFile($key, $value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        //If admin model has been given
        if ( $value instanceof AdminFile ) {
           return $value->filename;
        }

        //If filename string has been given
        else if ( $value ) {
            return $value;
        }
    }
}