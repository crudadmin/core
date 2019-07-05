<?php

namespace Admin\Core\Tests;

use Admin\Core\Providers\AppServiceProvider;
use Illuminate\Support\Facades\File;

trait OrchestraSetup
{
    /*
     * Register all admin models into each test
     */
    protected $loadAllAdminModels = false;

    protected function getPackageProviders($app)
    {
        return [
            AppServiceProvider::class,
        ];
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($app, $path)
    {
        if (! $app->routesAreCached()) {
            require $path;
        }
    }

    /**
     * Setup default admin environment
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     */
    protected function setAdminEnvironmentSetUp($app)
    {
        //Bind app path
        $app['path'] = $this->getStubPath('app');

        // Setup default database to use sqlite :memory:
        $app['config']->set('app.debug', true);
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'crudadmin_v2_core');
        $app['config']->set('database.connections.mysql.username', 'homestead');
        $app['config']->set('database.connections.mysql.password', 'secret');

        // Setup default database to use sqlite :memory:
        $app['config']->set('admin.app_namespace', 'Admin\Core\Tests\App');

        //Register all admin models by default
        if ( $this->loadAllAdminModels === true )
            $this->registerAllAdminModels();
    }

    /**
     * Define environment setup.
     *
     * @param  \IllumcreateApplicationinate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setAdminEnvironmentSetUp($app);
    }

    /*
     * Return testing laravel app path
     */
    protected function getAppPath($path = null)
    {
        return $this->getStubPath('app'.($path ? '/'.$path : ''));
    }

    /*
     * Return stub path
     */
    public function getStubPath($path = null)
    {
        return __DIR__.'/Stubs/'.ltrim($path, '/');
    }

    /*
     * Delete file, or whole directory
     */
    protected function deleteFileOrDirectory($path)
    {
        if ( is_dir($path) )
            File::deleteDirectory($path);
        else
            @unlink($path);
    }

    /*
     * Register all admin models paths
     */
    public function registerAllAdminModels()
    {
        config()->set('admin.models', [
            'Admin\Core\Tests\App\Models' => $this->getAppPath('Models')
        ]);
    }
}