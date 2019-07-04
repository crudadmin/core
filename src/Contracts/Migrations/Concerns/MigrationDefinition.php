<?php

namespace Admin\Core\Contracts\Migrations\Concerns;

class MigrationDefinition
{
    /*
     * Migration command
     */
    protected $command = null;

    /*
     * Set command
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /*
     * Get command
     */
    public function getCommand()
    {
        return $this->command;
    }
}