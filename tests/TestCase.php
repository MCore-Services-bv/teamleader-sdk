<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests;

use Illuminate\Foundation\Application;
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
     * The token table, as `php artisan migrate` creates it.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get package providers
     *
     * @param  Application  $app
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
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default environment configurations
        $app['config']->set('teamleader.client_id', 'test_client_id');
        $app['config']->set('teamleader.client_secret', 'test_client_secret');
        $app['config']->set('teamleader.redirect_uri', 'http://localhost/callback');
        $app['config']->set('teamleader.rate_limiting.enabled', false);
        $app['config']->set('teamleader.error_handling.throw_exceptions', true);

        // Use array cache for testing
        $app['config']->set('cache.default', 'array');

        $this->configureRedis($app);
    }

    /**
     * Configure the Redis connection used by the #[Group('redis')] tests.
     *
     * Defaults to predis, a pure-PHP client, so the suite does not require the
     * phpredis C extension to be compiled into whichever PHP the contributor
     * happens to be running. Set REDIS_CLIENT=phpredis to use the extension
     * where it is available.
     *
     * Database 15 keeps the sliding-window keys away from application data,
     * which normally lives in database 0.
     *
     * Override the host and port via environment variables when Redis is not on
     * the default address — Laravel Herd, for example, serves it on 6380:
     *
     *     REDIS_PORT=6380 vendor/bin/phpunit --group=redis
     *
     * @param  Application  $app
     */
    protected function configureRedis($app): void
    {
        $app['config']->set('database.redis.client', env('REDIS_CLIENT', 'predis'));

        $app['config']->set('database.redis.options', [
            'cluster' => 'redis',
            'prefix' => '',
        ]);

        $app['config']->set('database.redis.default', [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 15),
        ]);
    }
}
