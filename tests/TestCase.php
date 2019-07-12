<?php

namespace Admin\Core\Tests;

use Admin\Core\Tests\Concerns\AdminIntegration;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Admin\Core\Tests\Concerns\MigrationAssertions;

class TestCase extends BaseTestCase
{
    use OrchestraSetup,
        AdminIntegration,
        MigrationAssertions;
}
