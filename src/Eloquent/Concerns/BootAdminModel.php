<?php

namespace Admin\Core\Eloquent\Concerns;

use AdminCore;

trait BootAdminModel
{
    /*
     * Closures with properties setters
     */
    protected $adminCachable = [];

    /*
     * Which admin properties should be cachced through all admin models
     */
    protected $cacheProperties = ['fillable', 'dates', 'casts', 'hidden'];

    /**
     * Save cachable fields properties which will be booted on model boot state
     *
     * @param  closure  $closure
     * @return void
     */
    public function cachableFieldsProperties(\Closure $closure)
    {
        $this->adminCachable[] = $closure;
    }

    /*
     * Boot cachable properties first time.
     * Generate all required laravel properties as hidden, fillable, visible etc... then cache this values
     * for given moodel, and next time when model will be initialized again, this properties will be received from cache
     */
    public function bootCachableProperties()
    {
        $table = $this->getTable();

        $cachedModels = AdminCore::get('booted_models', []);

        //Check if model has been cached into admin cache
        if ( !array_key_exists($table, $cachedModels) ) {
            $cachedProperties = [];

            //Boot all saved callbacks
            foreach ($this->adminCachable as $callback) {
                $callback();
            }

            //Save booted properties
            foreach ($this->cacheProperties as $key) {
                $cachedProperties[$key] = $this->{$key};
            }

            AdminCore::push('booted_models', $cachedProperties, $this->getTable());
        } else {
            $cachedProperties = AdminCore::get('booted_models', [])[$table];
        }

        //Set all admin model properties from cache
        foreach ($cachedProperties as $key => $property) {
            $this->{$key} = $property;
        }
    }
}
