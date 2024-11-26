<?php

namespace Admin\Core\Casts;

use Admin\Core\Casts\Concerns\AdminCast;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;

class GutenbergCast implements CastsAttributes, AdminCast
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
        if ( \Admin::isAdmin() ) {
            return $value;
        }

        $builder = new \Admin\Gutenberg\Contracts\Blocks\BlocksBuilder($value);

        return $builder->render();
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