<?php

namespace Admin\Core\Fields\Mutations;

class BelongsToAttributeMutator
{
    public $attributes = ['belongsTo', 'belongsToMany'];

    /*
     * Create new field with correct relation key name
     */
    public function create( $field, $key )
    {
        $add = [];

        if ( array_key_exists('belongsTo', $field) && substr($key, -3) != '_id' ) {
            $add[$key.'_id'] = $field;
        }

        return $add;
    }

    /*
     * Remove old name of field
     */
    public function remove($field, $key)
    {
        if ( array_key_exists('belongsTo', $field) && substr($key, -3) != '_id' ) {
            return true;
        }
    }
}
?>