<?php

namespace Admin\Core\Providers;

use Admin\Core\Commands\AdminMigrationCommand;
use Admin\Core\Commands\AdminModelCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
        $this->app->bind('crudadmin::admin.model', AdminModelCommand::class);
        $this->app->bind('crudadmin::admin.migrate', AdminMigrationCommand::class);

        $this->commands([
            'crudadmin::admin.model',
            'crudadmin::admin.migrate',
        ]);
    }
}