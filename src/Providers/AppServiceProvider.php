<?php

namespace Gogol\Invoices\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [

    ];

    protected $facades = [

    ];

    protected $routeMiddleware = [

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

        $this->bootFacades();

        $this->bootProviders();
    }

    public function bootFacades()
    {
        $this->app->booting(function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $alias => $facade)
            {
                $loader->alias($alias, $facade);
            }

        });
    }

    public function bootProviders($providers = null)
    {
        foreach ($providers ?: $this->providers as $provider) {
            app()->register($provider);
        }
    }
}