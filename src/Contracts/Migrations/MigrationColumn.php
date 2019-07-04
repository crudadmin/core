<?php

namespace Admin\Core\Contracts\Migrations;

use Admin\Core\Contracts\Migrations\Concerns\HasIndex;
use Admin\Core\Contracts\Migrations\Concerns\MigrationEvents;
use Admin\Core\Eloquent\AdminModel;
use Illuminate\Database\Schema\Blueprint;

class MigrationColumn
{
    use HasIndex,
        MigrationEvents;

    /*
     * Migration command
     */
    protected $command = null;

    /*
     * Column name
     */
    protected $column = null;

    /*
     * Set command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /*
     * Get command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /*
     * Get column name
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Check if can apply given column
     * @param  AdminModel  $model
     * @return boolean
     */
    public function isColumnEnabled(AdminModel $model)
    {
        return false;
    }

    /**
     * Register static column
     * @param  Blueprint    $table
     * @param  AdminModel   $model
     * @param  bool         $update
     * @return Blueprint
     */
    public function registerStaticColumn(Blueprint $table, AdminModel $model, bool $update, $columnExists = null)
    {

    }

    /*
     * Set input of field for line, writeln support etx...
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }

    /*
     * Set output of field for line, writeln support etx...
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}