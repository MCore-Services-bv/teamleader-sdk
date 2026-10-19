<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Pulse;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use McoreServices\TeamleaderSDK\Events\RateLimitWaited;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Events\ResponseReceived;
use McoreServices\TeamleaderSDK\Pulse\Entries;
use McoreServices\TeamleaderSDK\Pulse\Recorders\TeamleaderCallers;
use McoreServices\TeamleaderSDK\Tests\Feature\Pulse\Fixtures\CallsTeamleaderJob;
use PHPUnit\Framework\Attributes\Group;

#[Group('pulse')]
final class TeamleaderCallersRecorderTest extends PulseTestCase
{
    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('queue.default', 'database');
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->get('/admin/quotations', function () {
            foreach (range(1, 14) as $i) {
                event(new ResponseReceived('POST', 'quotations.info', 200, 50.0, connection: 'brand-a'));
            }

            event(new RateLimitWaited(1000, 90.0, 'quotations.info', connection: 'brand-a'));

            return 'ok';
        });

        $router->get('/admin/dashboard', fn () => 'no Teamleader here');

        $router->get('/admin/sync', function () {
            event(new ResponseReceived('POST', 'deals.list', 200, 20.0));
            Artisan::call('teamleader-test:sync');

            return 'ok';
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::command('teamleader-test:sync', function () {
            event(new ResponseReceived('POST', 'deals.list', 200, 30.0));
            event(new ResponseReceived('POST', 'deals.list', 200, 30.0));
        });
    }

    // -- web requests -----------------------------------------------------------

    public function test_a_route_that_calls_teamleader_is_recorded(): void
    {
        $this->get('/admin/quotations')->assertOk();

        $key = Entries::key(['request', 'GET /admin/quotations', 'Closure']);

        $time = $this->entries(Entries::CALLER_TIME);
        $this->assertSame([$key], $time->pluck('key')->all());
        // 14 calls of 50 ms, and 1 s waiting for the rate limit
        $this->assertSame(1700, (int) $time[0]->value);

        $this->assertSame(14, (int) $this->entries(Entries::CALLER_CALLS)[0]->value);
        $this->assertGreaterThanOrEqual(1700, (int) $this->entries(Entries::CALLER_DURATION)[0]->value);
    }

    public function test_a_route_that_does_not_call_teamleader_is_not_recorded(): void
    {
        $this->get('/admin/dashboard')->assertOk();

        $this->assertCount(0, $this->entries(Entries::CALLER_TIME));
    }

    public function test_calls_made_outside_a_request_do_not_leak_into_the_next_one(): void
    {
        $this->get('/admin/quotations')->assertOk();
        $this->get('/admin/dashboard')->assertOk();

        $this->assertSame(
            [Entries::key(['request', 'GET /admin/quotations', 'Closure'])],
            $this->entries(Entries::CALLER_TIME)->pluck('key')->all(),
        );
    }

    public function test_calls_made_before_a_request_are_not_counted_toward_it(): void
    {
        event(new ResponseReceived('POST', 'deals.list', 200, 999.0));

        $this->get('/admin/quotations')->assertOk();

        $this->assertSame(1700, (int) $this->entries(Entries::CALLER_TIME)[0]->value);
    }

    public function test_a_command_called_from_a_request_counts_toward_both(): void
    {
        // As in production: Laravel routes command events outside unit tests
        $this->onTheCommandLine();

        $this->get('/admin/sync')->assertOk();

        $this->assertSame([
            Entries::key(['command', 'teamleader-test:sync', null]) => 60,
            Entries::key(['request', 'GET /admin/sync', 'Closure']) => 80,
        ], $this->entries(Entries::CALLER_TIME)->pluck('value', 'key')->map(fn ($v) => (int) $v)->sortKeys()->all());
    }

    public function test_a_request_that_timed_out_counts_its_wait(): void
    {
        $this->onTheCommandLine();
        Artisan::command('teamleader-test:download', function () {
            event(new RequestFailed('POST', 'invoices.download', null, 'cURL error 28: timed out', durationMs: 30000.0));
            event(new RequestFailed('POST', 'invoices.draft', 422, 'Invalid'));
        });

        Artisan::call('teamleader-test:download');

        $time = $this->entries(Entries::CALLER_TIME);
        $this->assertSame([Entries::key(['command', 'teamleader-test:download', null])], $time->pluck('key')->all());
        $this->assertSame(30000, (int) $time[0]->value);
        $this->assertSame(1, (int) $this->entries(Entries::CALLER_CALLS)[0]->value);
    }

    public function test_ignored_routes_are_not_recorded(): void
    {
        Config::set('pulse.recorders.'.TeamleaderCallers::class.'.ignore', ['#^/admin/quotations$#']);

        $this->get('/admin/quotations')->assertOk();

        $this->assertCount(0, $this->entries(Entries::CALLER_TIME));
    }

    // -- commands --------------------------------------------------------------

    public function test_an_artisan_command_is_recorded(): void
    {
        $this->onTheCommandLine();

        Artisan::call('teamleader-test:sync');

        $time = $this->entries(Entries::CALLER_TIME);

        $this->assertSame([Entries::key(['command', 'teamleader-test:sync', null])], $time->pluck('key')->all());
        $this->assertSame(60, (int) $time[0]->value);
        $this->assertSame(2, (int) $this->entries(Entries::CALLER_CALLS)[0]->value);
    }

    public function test_a_command_started_by_another_counts_toward_both(): void
    {
        $this->onTheCommandLine();

        Artisan::command('teamleader-test:nightly', function () {
            event(new ResponseReceived('POST', 'deals.list', 200, 15.0));
            Artisan::call('teamleader-test:sync');
        });

        Artisan::call('teamleader-test:nightly');

        $this->assertSame([
            Entries::key(['command', 'teamleader-test:nightly', null]) => 75,
            Entries::key(['command', 'teamleader-test:sync', null]) => 60,
        ], $this->entries(Entries::CALLER_TIME)->pluck('value', 'key')->map(fn ($v) => (int) $v)->sortKeys()->all());
    }

    public function test_a_command_run_with_call_is_part_of_its_caller(): void
    {
        $this->onTheCommandLine();

        Artisan::command('teamleader-test:weekly', function () {
            event(new ResponseReceived('POST', 'deals.list', 200, 15.0));
            $this->call('teamleader-test:sync');
        });

        Artisan::call('teamleader-test:weekly');

        $this->assertSame(
            [Entries::key(['command', 'teamleader-test:weekly', null]) => 75],
            $this->entries(Entries::CALLER_TIME)->pluck('value', 'key')->map(fn ($v) => (int) $v)->all(),
        );
    }

    public function test_a_long_running_worker_is_not_a_caller(): void
    {
        $this->onTheCommandLine();

        Bus::dispatchToQueue(new CallsTeamleaderJob(calls: 1));

        Artisan::call('queue:work', ['--max-jobs' => 1, '--stop-when-empty' => true, '--sleep' => 0]);

        $this->assertSame(
            [Entries::key(['job', CallsTeamleaderJob::class, null])],
            $this->entries(Entries::CALLER_TIME)->pluck('key')->all(),
        );
    }

    // -- queued jobs -------------------------------------------------------------

    public function test_a_queued_job_is_recorded_and_the_worker_is_not(): void
    {
        Bus::dispatchToQueue(new CallsTeamleaderJob(calls: 3, msPerCall: 100.0, waitMs: 500));

        Artisan::call('queue:work', ['--max-jobs' => 1, '--stop-when-empty' => true, '--sleep' => 0]);

        $time = $this->entries(Entries::CALLER_TIME);

        $this->assertSame([Entries::key(['job', CallsTeamleaderJob::class, null])], $time->pluck('key')->all());
        $this->assertSame(800, (int) $time[0]->value);
        $this->assertSame(3, (int) $this->entries(Entries::CALLER_CALLS)[0]->value);
    }

    public function test_a_failed_job_is_recorded_too(): void
    {
        Bus::dispatchToQueue(new CallsTeamleaderJob(calls: 2, msPerCall: 100.0, fail: true));

        Artisan::call('queue:work', ['--max-jobs' => 1, '--stop-when-empty' => true, '--sleep' => 0]);

        $time = $this->entries(Entries::CALLER_TIME);

        $this->assertSame([Entries::key(['job', CallsTeamleaderJob::class, null])], $time->pluck('key')->all());
        $this->assertSame(200, (int) $time[0]->value);
    }

    public function test_a_job_on_the_sync_queue_belongs_to_whatever_dispatched_it(): void
    {
        $this->onTheCommandLine();
        Config::set('queue.default', 'sync');

        Artisan::command('teamleader-test:dispatch', function () {
            CallsTeamleaderJob::dispatch(2, 100.0);
        });

        Artisan::call('teamleader-test:dispatch');

        $this->assertSame(
            [Entries::key(['command', 'teamleader-test:dispatch', null])],
            $this->entries(Entries::CALLER_TIME)->pluck('key')->all(),
        );
    }

    public function test_sampling_applies(): void
    {
        $this->onTheCommandLine();
        Config::set('pulse.recorders.'.TeamleaderCallers::class.'.sample_rate', 0);

        Artisan::call('teamleader-test:sync');

        $this->assertCount(0, $this->entries(Entries::CALLER_TIME));
    }

    /**
     * What `php artisan …` does before running a command: route Symfony's
     * console events to Laravel's CommandStarting and CommandFinished.
     */
    private function onTheCommandLine(): void
    {
        $kernel = $this->app->make(ConsoleKernel::class);
        $kernel->rerouteSymfonyCommandEvents();
        $kernel->setArtisan(null);
    }
}
