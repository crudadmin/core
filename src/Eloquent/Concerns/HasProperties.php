<?php

namespace Admin\Core\Eloquent\Concerns;

trait HasProperties
{
    /**
     * If properties are being mutated in mutator, we want turn of module mutators inside module mutators
     *
     * @var  bool
     */
    private $mutatingInMutator = false;

    /**
     * This properties can be called also as method
     *
     * @var  array
     */
    static $callableProperties = [
        'name', 'fields', 'active', 'inMenu', 'single', 'options',
        'insertable', 'editable', 'publishable', 'deletable',
        'settings', 'buttons', 'reserved', 'layouts', 'belongsToModel'
    ];

    /**
     * Returns property.
     *
     * @param  string  $property
     * @param  Admin\Core\Eloquent\AdminModel  $row
     * @return mixed
     */
    public function getProperty(string $property, $row = null)
    {
        //Laravel for translatable properties
        if (
            in_array($property, ['name', 'title'])
            && property_exists($this, $property)
            && $translate = trans($this->{$property})
        ) {
            return $translate;
        }

        //Object / Array
        elseif (in_array($property, self::$callableProperties)) {
            if (method_exists($this, $property)) {
                return $this->mutateAdminModelProperty($property, $this->{$property}($row));
            }

            if (property_exists($this, $property)) {
                return $this->mutateAdminModelProperty($property, $this->{$property});
            }
        }

        else if (property_exists($this, $property)) {
            return $this->mutateAdminModelProperty($property, $this->{$property});
        }

        return $this->mutateAdminModelProperty($property);
    }

    private function mutateAdminModelProperty($property, $value = null)
    {
        //We need disable mutating properties inside module
        if ( $this->mutatingInMutator === true ){
            return $value;
        }

        $this->mutatingInMutator = true;

        $this->runAdminModules(function($module) use (&$value, $property) {
            $mutatorMethodName = 'set'.$property.'Property';

            if ( method_exists($module, $mutatorMethodName) ) {
                $value = $module->{$mutatorMethodName}($value);
            }
        });

        $this->mutatingInMutator = false;

        return $value;
    }

}