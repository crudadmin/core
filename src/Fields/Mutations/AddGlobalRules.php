<?php

namespace Admin\Core\Fields\Mutations;

use Fields;

class AddGlobalRules
{
    /**
     * Add additional parameter rules into exact types from additional config.
     *
     * @param  array  $field
     * @return array
     */
    public function update(array $field)
    {
        $global_rules = config('admin.global_rules', []);

        //If is not set field type, default will be string
        if (! array_key_exists('type', $field)) {
            $field['type'] = 'string';
        }

        foreach ($global_rules as $type => $rules) {
            if ($field['type'] == $type) {
                $field = $field + Fields::mutate(FieldToArray::class, $rules);
            }
        }

        return $field;
    }
}
