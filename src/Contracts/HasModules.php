<?php

namespace Admin\Core\Contracts;

use Admin\Core\Eloquent\Concerns\AdminModelModule;

trait HasModules
{
    private $cachedModules = [];

    public function getModulesConfigPaths()
    {
        return $this->config()['modules'];
    }

    public function getGlobalModulesAutoLoad()
    {
        $key = implode(';', $this->getModulesConfigPaths());

        if ( !array_key_exists($key, $this->cachedModules) ){
            $this->cachedModules = [
                $key => $this->getGlobalModulesNamespaces(),
            ];
        }

        return $this->cachedModules[$key];
    }

    private function getGlobalModulesNamespaces()
    {
        $modules = [];

        $modulesPaths = $this->getNamespacesList('modules');

        foreach ($modulesPaths as $path => $namespace) {
            $files = $this->getNamespaceFiles($path);

            foreach ($files as $file) {
                $module = $this->fromFilePathToNamespace((string) $file, dirname($path), $namespace);

                if ( class_exists($module) && is_a($module, AdminModelModule::class, true) ) {
                    $modules[] = $module;
                }
            }
        }

        return $modules;
    }
}
