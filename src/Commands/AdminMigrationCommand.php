<?php

namespace Admin\Core\Commands;

use Admin\Core\Migrations\MigrationBuilder;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use AdminCore;

class AdminMigrationCommand extends MigrationBuilder
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations from all admin models';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $models = AdminCore::getAdminModels();

        $this->migrate($models);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Forced migration of all models'],
            ['auto-drop', null, InputOption::VALUE_NONE, 'Automatically drop all unnecessary columns'],
        ];
    }
}
