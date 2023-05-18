<?php

namespace Admin\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

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
            $file = is_string($file) ? json_decode($file, true) : $file;

            return array_map(function($file) use ($model, $key) {
                return $model->getAdminFile($key, $file);
            }, array_wrap($file));
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
        return $value;
    }
}