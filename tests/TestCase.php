<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Tests;

use DerrickOb\Pricer\Laravel\PricerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PricerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('pricer.default_currency', 'USD');
        $app['config']->set('pricer.precision', 2);
    }
}
