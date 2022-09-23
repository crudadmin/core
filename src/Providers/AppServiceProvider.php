<?php

namespace Admin\Core\Providers;

use Admin\Core\Facades;
use Admin\Core\Fields;
use Admin\Core\Helpers;
use Admin\Core\Helpers\Storage\AdminFile;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        CommandsServiceProvider::class,
        \Intervention\Image\ImageServiceProvider::class,
    ];

    protected $facades = [
        'admin.fields' => [
            'classname' => 'Fields',
            'facade' => Facades\Fields::class,
            'helper' => Fields\Fields::class,
        ],
        'admin.core' => [
            'classname' => 'AdminCore',
            'facade' => Facades\AdminCore::class,
            'helper' => Helpers\AdminCore::class,
        ],
        'admin.store' => [
            'classname' => 'DataStore',
            'facade' => Facades\DataStore::class,
            'helper' => Helpers\DataStore::class,
        ],
        'imagecompressor' => [
            'classname' => 'ImageCompressor',
            'facade' => Facades\ImageCompressor::class,
            'helper' => Helpers\ImageCompressor\ImageCompressor::class,
        ],
        'Image' => [
            'facade' => \Intervention\Image\Facades\Image::class,
        ],
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //Load translations
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang/', 'admin.core');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //Merge configs
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'admin'
        );

        $this->registerFacades();

        $this->registerProviders();

        $this->addCrudadminStorage();
    }

    /*
     * Register facades helpers and aliases
     */
    public function registerFacades()
    {
        //Register facades
        foreach ($this->facades as $alias => $facade) {
            if ( isset($facade['helper']) ) {
                $this->app->bind($alias, $facade['helper']);
            }
        }

        //Register aliasess
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $key => $facade) {
                $loader->alias($facade['classname'] ?? $key, $facade['facade']);
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

    /*
     * Register crudadmin storage
     */
    private function addCrudadminStorage()
    {
        $crudAdminStoragePath = storage_path('crudadmin');

        $this->app['config']->set('filesystems.disks.crudadmin', [
            'driver' => 'local',
            'root' => $crudAdminStoragePath,
            'url' => env('APP_URL'),
            'visibility' => 'public',
        ]);

        $this->app['config']->set('filesystems.disks.crudadmin.uploads', [
            'driver' => 'local',
            'root' => $uploadsDirectory = $crudAdminStoragePath.'/'.AdminFile::UPLOADS_DIRECTORY,
            'url' => (env('ASSET_URL') ?: env('APP_URL')).'/'.AdminFile::UPLOADS_DIRECTORY,
            'visibility' => 'public',
        ]);

        $this->app['config']->set('filesystems.disks.crudadmin.uploads_private', [
            'driver' => 'local',
            'root' => $uploadsDirectory = $crudAdminStoragePath.'/'.AdminFile::UPLOADS_DIRECTORY.'_private',
            'url' => (env('ASSET_URL') ?: env('APP_URL')).'/'.AdminFile::UPLOADS_DIRECTORY,
            'visibility' => 'private',
        ]);

        $this->app['config']->set('filesystems.disks.crudadmin.lang', [
            'driver' => 'local',
            'root' => $crudAdminStoragePath.'/lang',
            'visibility' => 'private',
        ]);

        $this->app['config']->set(
            'filesystems.links',
            array_merge(
                $this->app['config']->get('filesystems.links', []), [
                    public_path(AdminFile::UPLOADS_DIRECTORY) => $uploadsDirectory
                ]
            )
        );
    }
}
