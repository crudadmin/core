<?php

namespace Admin\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class CommandsServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    private function registerCommands()
    {
        $this->app->bind('crudadmin::admin.model', \Admin\Core\Commands\AdminModelCommand::class);

        $this->commands([
            'crudadmin::admin.model',
        ]);
    }
}