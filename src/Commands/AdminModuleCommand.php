<?php

namespace Admin\Core\Commands;

use AdminCore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class AdminModuleCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:module';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Admin Module into crudadmin';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Admin module';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(new Filesystem);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $path = __DIR__.'/../Stubs/AdminModule.stub';

        AdminCore::fire('admin.command.module.create.stub_path', [&$path, $this]);

        return $path;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    public function getNameInput()
    {
        return $this->qualifyClass(trim($this->argument('name')));
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        //We can mutate generating stub via $stub reference
        AdminCore::fire('admin.command.model.create.stub', [&$stub, $this]);

        $stub = str_replace('DummyNamespace', $this->getNamespace($name), $stub);

        return $this;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [
            ['name', '', InputOption::VALUE_OPTIONAL, 'Module name'],
        ];

        /*
         * We can mutate module options with $options reference
         */
        AdminCore::fire('admin.command.module.create.options', [&$options, $this]);

        return $options;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace().'Admin\Modules\\';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace_first($this->rootNamespace(), '', $name);

        return app_path('Admin/Modules/'.str_replace('\\', '/', $name).'.php');
    }
}
