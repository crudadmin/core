<?php

namespace Admin\Core\Tests\Concerns;

use Admin\Core\Tests\Concerns\DropDatabase;
use Admin\Core\Tests\Concerns\DropUploads;

trait AdminIntegration
{
    /**
     * Setup the test environment.
     */
    protected function tearDown() : void
    {
        $this->registerTraits();

        parent::tearDown();
    }

    /*
     * Register all traits instances
     */
    protected function registerTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        //Registers own event for dropping database after test
        if (isset($uses[DropDatabase::class])) {
            $this->dropDatabase();
        }
    }

    /**
     * Return object of class
     * @param  string/object $model
     * @return object
     */
    public function getModelClass($model)
    {
        return is_object($model) ? $model : new $model;
    }
}