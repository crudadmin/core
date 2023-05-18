<?php

namespace Admin\Core\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class DateableCast implements CastsAttributes
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

        if ( $model->isFieldType($key, 'time') ){
            $parse = function($time){
                $format = strlen($time) == '5' ? 'H:i' : 'H:i:s';

                return Carbon::createFromFormat($format, $time);
            };
        } else if ( $model->isFieldType($key, 'date') ){
            $parse = function($date){
                $format = preg_match('/^[0-9]{2}.([0-9]{2}).([0-9]{4})$/', $date)
                            ? 'd.m.Y' //Legacy depreaced format support
                            : 'Y-m-d';

                return Carbon::createFromFormat($format, $date)->setTimezone(config('app.timezone'))->setTime(0, 0, 0, 0);
            };
        } else if ( $model->isFieldType($key, ['datetime', 'timestamp']) ){
            $parse = function($date){
                if ( $date == '0000-00-00 00:00:00' ){
                    return;
                }

                return (new Carbon($date))->setTimezone(config('app.timezone'));
            };
        }

        //Array is when dates are localized
        if (is_array($value) || $model->hasFieldParam($key, ['multiple'], true)) {
            $values = collect(
                is_string($value)
                    ? json_decode($value, true)
                    : array_wrap($value)
            )->filter();

            return $values->map(function($time) use ($parse) {
                return $parse($time);
            });
        }

        return $value ? $parse($value) : null;
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
        $parse = function($value) use ($model, $key) {
            if ( $model->isFieldType($key, 'time') ){
                return $value->format('H:i:s');
            } else if ( $model->isFieldType($key, 'date') ){
                return $value->format('Y-m-d');
            } else if ( $model->isFieldType($key, ['datetime', 'timestamp']) ){
                return $value->format('Y-m-d H:i:s');
            }

            return $value->toJson();
        };

        if ( $value instanceof Carbon ) {
            return $parse($value);
        } else if ( $value instanceof Collection || is_array($value) ) {
            if ( is_array($value) ){
                $value = collect($value);
            }

            $value = $value->map(function($value) use ($parse) {
                return $parse($value);
            })->toJson();
        }

        return $value;
    }
}