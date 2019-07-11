<?php

namespace Admin\Core\Fields\Mutations;

class BelongsToAttributeMutator
{
    public $attributes = ['belongsTo', 'belongsToMany'];

    /**
     * Create new field with correct relation key name.
     *
     * @param  array  $field
     * @param  string  $key
     * @return array
     */
    public function create(array $field, string $key)
    {
        $add = [];

        if (array_key_exists('belongsTo', $field) && substr($key, -3) != '_id') {
            $add[$key.'_id'] = $field;
        }

        return $add;
    }

    /**
     * Remove old belongsTo field with wrong name without _id prefix after column.
     *
     * @param  array  $field
     * @param  string  $key
     * @return bool|array|string
     */
    public function remove(array $field, string $key)
    {
        if (array_key_exists('belongsTo', $field) && substr($key, -3) != '_id') {
            return true;
        }
    }
}
