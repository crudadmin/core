<?php

namespace Admin\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class LocalizedJsonCast implements CastsAttributes
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
        $array = json_decode($value, true);

        if ( ($model::$localizedResponseArray === false || $model->isSocalizedResponseLocalArray()) && $model->isForcedLocalizedArray() === false ) {
            return $model->returnLocaleValue($array);
        }

        return $array;
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
        return json_encode($value);
    }
}