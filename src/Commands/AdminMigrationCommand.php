<?php

namespace Admin\Core\Commands;

use AdminCore;
use Illuminate\Console\ConfirmableTrait;
use Admin\Core\Migrations\MigrationBuilder;
use Symfony\Component\Console\Input\InputOption;

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
            ['unknown', 'u', InputOption::VALUE_NONE, 'Check uknown tables and ask to delete them'],
            ['auto-drop', null, InputOption::VALUE_NONE, 'Automatically drop all unnecessary columns'],
        ];
    }
}
