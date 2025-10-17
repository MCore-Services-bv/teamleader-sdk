<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests;

use McoreServices\TeamleaderSDK\TeamleaderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup if needed
    }

    /**
     * Get package providers
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TeamleaderServiceProvider::class,
        ];
    }

    /**
     * Define environment setup
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default environment configurations
        $app['config']->set('teamleader.client_id', 'test_client_id');
        $app['config']->set('teamleader.client_secret', 'test_client_secret');
        $app['config']->set('teamleader.redirect_uri', 'http://localhost/callback');
        $app['config']->set('teamleader.caching.enabled', false);
        $app['config']->set('teamleader.rate_limiting.enabled', false);
        $app['config']->set('teamleader.error_handling.throw_exceptions', true);

        // Use array cache for testing
        $app['config']->set('cache.default', 'array');
    }
}
