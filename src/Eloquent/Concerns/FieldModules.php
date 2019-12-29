<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;

trait FieldModules
{
    public function getModules()
    {
        return $this->modules;
    }

    public function addModule($module)
    {
        $this->modules[] = $module;
    }

    /*
     * Returns cached admin rule class
     */
    protected function getCachedAdminModuleClass($class)
    {
        $storeKey = 'admin_modules';

        $store = AdminCore::get($storeKey, []);

        //If key does exists in store
        if ( array_key_exists($class, $store) ) {
            return $store[$class];
        }

        return AdminCore::push($storeKey, new $class(), $class);
    }

    /*
     * Return and fire admin rules
     */
    public function runAdminModules($callback)
    {
        if ($this->modules && is_array($this->modules)) {
            foreach ($this->modules as $class) {
                $module = $this->getCachedAdminModuleClass($class);

                if ( $module->isActive($this) === true ) {
                    $callback($module);
                }
            }
        }
    }
}
