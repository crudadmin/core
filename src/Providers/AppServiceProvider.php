<?php

namespace Admin\Core\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [

    ];

    protected $facades = [
        'admin_core' => [
            'classname' => 'AdminCore',
            'facade' => Admin\Core\Facades\AdminCore::class,
            'helper' => Admin\Core\Helpers\AdminCore::class,
        ],
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'admin.core'
        );

        $this->registerFacades();

        $this->registerProviders();
    }

    /*
     * Register facades helpers and aliases
     */
    public function registerFacades()
    {
        //Register facades
        foreach ($this->facades as $alias => $facade)
        {
            $this->app->bind($alias, $facade['helper']);
        }

        //Register aliasess
        $this->app->booting(function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $facade)
            {
                $loader->alias($facade['classname'], $facade['facade']);
            }
        });
    }

    /*
     * Register service providers
     */
    public function registerProviders($providers = null)
    {
        foreach ($providers ?: $this->providers as $provider) {
            app()->register($provider);
        }
    }
}