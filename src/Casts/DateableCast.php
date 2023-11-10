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
        if ( !$value || $value == '0000-00-00 00:00:00' ){
            return;
        }

        $type = $model->getFieldType($key);

        if ( $type == 'time' ){
            $format = strlen($value) == '5' ? 'H:i' : 'H:i:s';

            if ( Carbon::hasFormat($value, $format) ) {
                return Carbon::createFromFormat($format, $value);
            }
        } else if ( $type == 'date' ){
            $format = preg_match('/^[0-9]{2}.([0-9]{2}).([0-9]{4})$/', $value)
                        ? 'd.m.Y' //Legacy depreaced format support
                        : 'Y-m-d';

            if ( Carbon::hasFormat($value, $format) ) {
                return Carbon::createFromFormat($format, $value)->setTimezone(config('app.timezone'))->setTime(0, 0, 0, 0);
            }
        } else if ( in_array($type, ['datetime', 'timestamp']) ){
            return (new Carbon($value))->setTimezone(config('app.timezone'));
        }
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
        $dbFormat = $this->getFinalDatabaseFormat($model, $key);

        //Correct format has been given already
        if ( is_string($value) ) {
            if ( Carbon::hasFormat($value, $dbFormat) ) {
                return $value;
            }

            //Guess given date string format
            $value = $this->getUniversalDateFormat($value, $model, $key);
        }

        //Formate carbon
        if ( $value && $value instanceof Carbon ) {
            return $dbFormat ? $value->format($dbFormat) : $value->toJson();
        }
    }

    private function getFinalDatabaseFormat($model, $key)
    {
        $fieldType = $model->getFieldType($key);

        if ( $fieldType == 'time' ){
            return 'H:i:s';
        } else if ( $fieldType == 'date' ){
            return 'Y-m-d';
        } else if ( in_array($fieldType, ['datetime', 'timestamp']) ){
            return 'Y-m-d H:i:s';
        }
    }

    private function getUniversalDateFormat($value, $model, $key)
    {
        $field = $model->getField($key);

        $supportedFormats = array_values(array_filter(array_merge(
            [ $field['date_format'] ?? null ],
            explode(',', $field['date_format_multiple'] ?? '')
        )));

        foreach ($supportedFormats as $format) {
            if ( !Carbon::hasFormat($value, $format) ){
                continue;
            }

            if ( strpos($value, 'Z') ) {
                $date = Carbon::createFromFormat($format, $value, 'UTC')->setTimezone(config('app.timezone'));
            } else {
                $date = Carbon::createFromFormat($format, $value);
            }

            return $this->resetDateFromFormat($date, $format);
        }
    }

    private function resetDateFromFormat($date, $format)
    {
        $reset = [
            'd' => ['day', 1],
            'm' => ['month', 1],
            'y' => ['year', 1970],
            'h' => ['hour', 0],
            'i' => ['minute', 0],
            's' => ['second', 0],
        ];

        $format = strtolower($format);

        foreach ($reset as $identifier => $arr) {
            //Reset hours if are not in date format
            if (strpos($format, $identifier) === false) {
                $date->{$arr[0]}($arr[1]);
            }
        }

        return $date;
    }
}