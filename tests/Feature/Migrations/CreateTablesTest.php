<?php

namespace Admin\Core\Tests\Feature\Migrations;

use Admin\Core\Tests\TestCase;
use Admin\Core\Tests\Concerns\DropDatabase;

class CreateTablesTest extends TestCase
{
    use DropDatabase;

    /*
     * Load all admin models
     */
    protected $loadAllAdminModels = true;

    /** @test */
    public function test_create_table_all_tables()
    {
        $this->artisan('admin:migrate')
             ->expectsOutput('Created table: fields_groups')
             ->expectsOutput('Created table: fields_types')
             ->expectsOutput('Created table: fields_mutators')
             ->expectsOutput('Created table: articles')
             ->expectsOutput('Created table: articles_comments')
             ->assertExitCode(0);
    }
}
