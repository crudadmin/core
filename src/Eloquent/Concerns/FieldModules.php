<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;

trait FieldModules
{
    static $globalModules = [];

    public function getModules()
    {
        return array_unique(array_merge($this->modules, self::$globalModules));
    }

    public function addModule($module)
    {
        $this->modules[] = $module;
    }

    public static function addGlobalModule($module)
    {
        self::$globalModules[] = $module;
        self::$globalModules = array_unique(self::$globalModules);
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
        if (($modules = $this->getModules()) && is_array($modules)) {
            foreach ($modules as $class) {
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
