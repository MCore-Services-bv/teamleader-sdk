<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\LivewireServiceProvider;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderRequests;
use McoreServices\TeamleaderSDK\TeamleaderServiceProvider;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use ReflectionClass;
use Throwable;

use function Orchestra\Testbench\default_migration_path;

/**
 * An application with Laravel Pulse (database storage, SQLite in memory) and
 * the SDK's recorders configured as the Monitoring guide shows.
 *
 * Skipped when laravel/pulse is not installed: the CI job without Pulse runs
 * the rest of the suite to prove the SDK does not need it.
 */
abstract class PulseTestCase extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PulseServiceProvider::class)) {
            $this->markTestSkipped('laravel/pulse is not installed');
        }

        parent::setUp();

        Pulse::flush();
        Pulse::handleExceptionsUsing(fn (Throwable $e) => throw $e);
    }

    protected function tearDown(): void
    {
        if (class_exists(PulseServiceProvider::class)) {
            Pulse::flush();
        }

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PulseServiceProvider::class,
            TeamleaderServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        // Laravel's own tables (jobs, for the tests that run a queue worker)
        $this->loadMigrationsFrom(default_migration_path());

        $this->loadMigrationsFrom(dirname((string) (new ReflectionClass(PulseServiceProvider::class))->getFileName(), 2).'/database/migrations');
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('queue.failed.driver', 'null');
        $app['config']->set('pulse.enabled', true);
        $app['config']->set('pulse.ingest.driver', 'storage');
        $app['config']->set('pulse.ingest.trim.lottery', [0, 1]);
        $app['config']->set('pulse.recorders', [
            TeamleaderRequests::class => [
                'enabled' => true,
                'sample_rate' => 1,
                'threshold' => 1000,
                'ignore' => [],
                'connections' => ['ignore' => [], 'groups' => []],
            ],
            TeamleaderCallers::class => [
                'enabled' => true,
                'sample_rate' => 1,
                'ignore' => [],
            ],
        ]);
    }

    /**
     * Entries of one type, as stored after Pulse::ingest().
     *
     * @return Collection<int, object>
     */
    protected function entries(string $type): Collection
    {
        Pulse::ingest();

        return Pulse::ignore(fn () => DB::table('pulse_entries')->where('type', $type)->orderBy('id')->get());
    }

    /**
     * The aggregate values of one type over the shortest period, keyed
     * "key|aggregate".
     *
     * @return array<string, float>
     */
    protected function aggregates(string $type): array
    {
        Pulse::ingest();

        return Pulse::ignore(fn () => DB::table('pulse_aggregates')
            ->where('type', $type)
            ->where('period', 60)
            ->get()
            ->groupBy(fn (object $row) => $row->key.'|'.$row->aggregate)
            ->map(fn (Collection $rows) => $rows->sum(fn (object $row) => (float) $row->value))
            ->all());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function states(): array
    {
        Pulse::ingest();

        return Pulse::ignore(fn () => DB::table('pulse_values')
            ->where('type', 'teamleader_connection_state')
            ->get()
            ->mapWithKeys(fn (object $row) => [$row->key => json_decode($row->value, true)])
            ->all());
    }
}
