<?php

namespace Admin\Core\Tests;

use Admin\Core\Tests\Concerns\AdminIntegration;
use Admin\Core\Tests\Concerns\FeatureAssertions;
use Admin\Core\Tests\OrchestraSetup;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use OrchestraSetup,
        AdminIntegration,
        FeatureAssertions;
}
