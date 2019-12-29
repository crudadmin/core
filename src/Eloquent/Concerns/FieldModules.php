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
        return AdminCore::cache($this->getTable().$class, function () use ($class) {
            return new $class($this);
        });
    }

    /*
     * Return and fire admin rules
     */
    public function runAdminModules($callback)
    {
        if ($this->modules && is_array($this->modules)) {
            foreach ($this->modules as $class) {
                $module = $this->getCachedAdminRuleClass($class);

                if ( $module->isActive($this) === true ) {
                    $callback($module);
                }
            }
        }
    }
}
