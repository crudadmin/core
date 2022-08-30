<?php

namespace Admin\Core\Fields;

class FieldsMutationBuilder
{
    /**
     * Add fields or groups before field.
     *
     * @var array
     */
    public $after = [];

    /**
     * Add fields or groups after field.
     *
     * @var array
     */
    public $before = [];

    /**
     * Remove fields from array.
     *
     * @var array
     */
    public $remove = [];

    /**
     * Add items at the end.
     *
     * @var  array
     */
    public $push = [];

    /**
     * Add items at the beggining.
     *
     * @var  array
     */
    public $push_before = [];

    /**
     * Modify group settings.
     *
     * @var  array
     */
    public $groups = [];

    /**
     * Modify fields.
     *
     * @var  array
     */
    public $fields = [];

    /**
     * Register adding fields after key.
     *
     * @param  string  $selectorKey
     * @param  array|Admin\Core\Fields\Group  $fields
     * @return $this
     */
    public function after(string $selectorKey, $fields)
    {
        //Add group
        if ($fields instanceof Group) {
            $this->after[$selectorKey][] = $fields;
        }

        //Or set of fields
        else {
            foreach ($fields as $key => $field) {
                if (is_numeric($key) && $field instanceof Group) {
                    $this->after[$selectorKey][] = $field;
                } else {
                    $this->after[$selectorKey][$key] = $field;
                }
            }
        }

        return $this;
    }

    /**
     * Register adding fields before key.
     *
     * @param  string  $selectorKey
     * @param  array|Admin\Core\Fields\Group  $fields
     * @return $this
     */
    public function before(string $selectorKey, $fields)
    {
        //Add group
        if ($fields instanceof Group) {
            $this->before[$selectorKey][] = $fields;
        }

        //Or set of fields
        else {
            foreach ($fields as $key => $field) {
                if (is_numeric($key) && $field instanceof Group) {
                    $this->before[$selectorKey][] = $field;
                } else {
                    $this->before[$selectorKey][$key] = $field;
                }
            }
        }

        return $this;
    }

    /**
     * Remove fields from model.
     *
     * @param  string|array  $selectorKey
     * @return $this
     */
    public function remove($selectorKey)
    {
        //Remove multiple fields/groups
        if (is_array($selectorKey)) {
            foreach ($selectorKey as $key) {
                $this->remove[] = $key;
            }
        }

        //Remove single item
        else {
            $this->remove[] = $selectorKey;
        }

        return $this;
    }

    /**
     * Added alias for removing/deleting fields/groups.
     *
     * @param  array|string  $selectorKey
     * @return $this
     */
    public function delete($selectorKey)
    {
        return $this->remove($selectorKey);
    }

    /**
     * Add fields into end of model.
     *
     * @param  array|Group  $fields
     * @param  array|string|null  $field
     * @param  string  $type
     * @return $this
     */
    public function push($fields, $field = null, $type = 'push')
    {
        //Push single line key with field data
        if ( !is_null($field) && is_string($fields) ) {
            $fields = [
                $fields => $field,
            ];
        }

        //Push group or fields
        if ($fields instanceof Group) {
            $this->{$type}[] = $fields;
        }

        //Push fields set
        else {
            foreach ($fields as $key => $field) {
                //We does not want to overide existing keys
                //so we need push items instead of inserting keys...
                if ( is_numeric($key) ) {
                    $this->{$type}[] = $field;
                } else {
                    $this->{$type}[$key] = $field;
                }
            }
        }

        return $this;
    }

    /**
     * Add group modification callback mutator.
     *
     * @param  string|array  $id
     * @param  closure  $callback
     * @return $this
     */
    public function group($id, $callback)
    {
        return $this->applyMultipleCallbacks($this->groups, $id, $callback);
    }

    /**
     * Push fields into given group
     *
     * @param  string  $id
     * @param  array|Group  $fields
     * @return  $this
     */
    public function pushGroup($id, $fields)
    {
        return $this->group($id, function($group) use ($fields) {
            $group->push($fields);
        });
    }

    /**
     * Add field modification callback mutator.
     *
     * @param  string|array  $key
     * @param  closure  $callback
     * @return $this
     */
    public function field($key, $callback)
    {
        return $this->applyMultipleCallbacks($this->fields, $key, $callback);
    }

    /**
     * Shortcuts, aliases.
     *
     * @param  string|array  $selectorKey
     * @param  array|Admin\Core\Fields\Group|string  $fields
     * @return $this
     */
    public function pushBefore($selectorKey, $fields = null)
    {
        if (is_null($fields) && (is_array($selectorKey) || is_object($selectorKey))) {
            return $this->push($selectorKey, null, 'push_before');
        }

        return $this->before($selectorKey, $fields);
    }

    /**
     * Push field or fields or group after given field.
     *
     * @param  string  $selectorKey
     * @param  array|Admin\Core\Fields\Group|string  $fields
     * @return $this
     */
    public function pushAfter(string $selectorKey, $fields)
    {
        return $this->after($selectorKey, $fields);
    }

    /**
     * Push field or fields or group before given field.
     *
     * @param  string  $selectorKey
     * @param  array|Admin\Core\Fields\Group|string  $fields
     * @return $this
     */
    public function addBefore(string $selectorKey, $fields)
    {
        return $this->before($selectorKey, $fields);
    }

    /**
     * Push field or fields or group after given field.
     *
     * @param  string  $selectorKey
     * @param  array|Admin\Core\Fields\Group|string  $fields
     * @return $this
     */
    public function addAfter(string $selectorKey, $fields)
    {
        return $this->after($selectorKey, $fields);
    }

    /**
     * Apply single callback or multiple callback from multiple keys.
     *
     * @param  array  &$property
     * @param  string|array  $key
     * @param  Closure  $callback
     * @return $this
     */
    private function applyMultipleCallbacks(&$property, $key, $callback)
    {
        $keys = array_wrap($key);

        foreach ($keys as $k) {
            if ( array_key_exists($k, $property) ) {
                $values = array_wrap($property[$k]);
                $values[] = $callback;

                $property[$k] = $values;
            } else {
                $property[$k] = $callback;
            }
        }

        return $this;
    }
}
