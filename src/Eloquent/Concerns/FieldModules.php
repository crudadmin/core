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
        $storeKey = 'admin_modules.'.$this->getTable();

        $store = AdminCore::get($storeKey, []);

        //If key does exists in store
        if ( array_key_exists($class, $store) ) {
            return $store[$class];
        }

        $initializedClass = new $class();
        $initializedClass->setModel($this);

        return AdminCore::push($storeKey, $initializedClass, $class);
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

    /*
     * On model boot, run boot method for all modules
     */
    public function bootAdminModules()
    {
        $this->runAdminModules(function($module) {
            if ( method_exists($module, 'boot') ) {
                $module->boot();
            }
        });
    }
}
