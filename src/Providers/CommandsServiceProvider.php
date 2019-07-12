<?php

namespace Admin\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Admin\Core\Commands\AdminModelCommand;
use Admin\Core\Commands\AdminMigrationCommand;

class CommandsServiceProvider extends ServiceProvider
{
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
