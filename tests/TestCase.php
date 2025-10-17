<?php

namespace McoreServices\TeamleaderSDK\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use McoreServices\TeamleaderSDK\TeamleaderServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TeamleaderServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Teamleader' => \McoreServices\TeamleaderSDK\Facades\Teamleader::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup Teamleader config
        $app['config']->set('teamleader.client_id', 'test_client_id');
        $app['config']->set('teamleader.client_secret', 'test_client_secret');
        $app['config']->set('teamleader.redirect_uri', 'http://localhost/callback');
        $app['config']->set('teamleader.error_handling.throw_exceptions', true);
    }
}
