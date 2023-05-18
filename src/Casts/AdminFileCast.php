<?php

namespace Admin\Core\Casts;

use Admin\Core\Helpers\Storage\AdminFile;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class AdminFileCast implements CastsAttributes
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
        if ($model->hasFieldParam($key, ['locale'], true)) {
            $value = (new LocalizedJsonCast)->get($model, $key, $value, $attributes);
        }

        //Array is when files are localized
        if (is_array($value) || $model->hasFieldParam($key, ['multiple'], true)) {
            $files = collect(
                is_string($value)
                    ? json_decode($value, true)
                    : array_wrap($value)
            );

            return $files->map(function($file) use ($model, $key) {
                return $model->getAdminFile($key, $file);
            });
        }

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
        if ( $value instanceof AdminFile ) {
           return $value->toArray();
        }

        //Localized files
        else if ( is_array($value) ) {
            return json_encode($value);
        }

        return $value;
    }
}