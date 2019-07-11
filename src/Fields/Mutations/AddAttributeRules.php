<?php

namespace Admin\Core\Fields\Mutations;

use Fields;

class AddAttributeRules
{
    /**
     * Merge conditionaly given fields with additional parameters from config files.
     *
     * @param  array  $field
     * @return array
     */
    public function update(array $field)
    {
        $custom_rules = config('admin.custom_rules', []);

        //Add custom rules
        foreach ($custom_rules as $rule => $rules) {
            if (array_key_exists($rule, $field) && $field[$rule] == true) {
                $rules = Fields::mutate(FieldToArray::class, $rules);

                $field = $rules + $field;

                if (array_key_exists('type', $rules)) {
                    $field['type'] = $rules['type'];
                }
            }
        }

        return $field;
    }
}
