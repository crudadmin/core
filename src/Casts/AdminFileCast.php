<?php

namespace Admin\Core\Casts;

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
    public function get($model, $key, $file, $attributes)
    {
        //If is multilanguage file/s
        if ($model->hasFieldParam($key, ['locale'], true)) {
            $file = $model->returnLocaleValue($file);
        }

        if (is_array($file) || $model->hasFieldParam($key, ['multiple'], true)) {
            $files = collect(
                is_string($file)
                    ? json_decode($file, true)
                    : array_wrap($file)
            );

            return $files->map(function($file) use ($model, $key) {
                return $model->getAdminFile($key, $file);
            });
        }

        return $model->getAdminFile($key, $file);
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
        if ( is_array($value) ){
            return json_encode($value);
        }

        return $value;
    }
}