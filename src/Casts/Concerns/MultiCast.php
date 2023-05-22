<?php

namespace Admin\Core\Casts\Concerns;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MultiCast implements CastsAttributes
{
    protected $casts;

    protected $cache;

    public function __construct(array $arguments, $cache = false)
    {
        $this->casts = array_filter($arguments);

        $this->cache = $cache;
    }

    public function get($model, $key, $value, $attributes)
    {
        foreach ($this->casts as $cast) {
            $value = $model->getMultyCastAttribute($key, $value, $cast, $this->cache);
        }

        return $value;
    }

    public function set($model, $key, $value, $attributes)
    {
        foreach ($this->casts as $cast) {
            $value = $model->setMultyCastAttribute($key, $value, $cast);
        }

        return $value;
    }
}