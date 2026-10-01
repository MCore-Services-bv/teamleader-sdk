<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature\Console;

use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\ResourceTestCase;

/**
 * v3.0 (§6): export, import and call — and the write guard in front of every
 * write: nothing without --write, a confirmation, --force in production.
 */
final class WriteCommandsTest extends ResourceTestCase
{
    private const COMPANY = '0b9a4c8e-1f2d-4e3a-9b5c-6d7e8f901234';

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/teamleader-cli-'.bin2hex(random_bytes(4));
        mkdir($this->directory);

        $api = $this->api;
        $this->app->instance(ConnectionManager::class, new class($api) extends ConnectionManager
        {
            public function __construct(private readonly TeamleaderSDK $api) {}

            public function connection(?string $name = null): TeamleaderSDK
            {
                return $this->api;
            }
        });
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);

        foreach (glob(storage_path('app/teamleader/import-*.json')) ?: [] as $file) {
            unlink($file);
        }

        parent::tearDown();
    }

    // -- export -----------------------------------------------------------------

    public function test_export_writes_the_file(): void
    {
        $this->api->queueListResponse([['id' => 'c1', 'name' => 'Acme'], ['id' => 'c2', 'name' => 'Globex']]);

        $this->artisan('teamleader:export', ['resource' => 'companies', '--output' => $this->path('companies.csv'), '--fields' => 'id,name'])
            ->expectsOutputToContain('2 records written to')
            ->assertExitCode(0);

        $this->assertSame("id,name\nc1,Acme\nc2,Globex\n", file_get_contents($this->path('companies.csv')));
    }

    // -- import: the guard ------------------------------------------------------

    public function test_import_without_write_sends_nothing(): void
    {
        $file = $this->dealsCsv(2);

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file])
            ->expectsOutputToContain('Nothing was sent')
            ->assertExitCode(1);

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_import_asks_before_sending(): void
    {
        $file = $this->dealsCsv(2);

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true])
            ->expectsConfirmation('Send 2 requests to Teamleader — create deals?', 'no')
            ->assertExitCode(1);

        $this->assertSame(0, $this->api->callCount());

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true])
            ->expectsConfirmation('Send 2 requests to Teamleader — create deals?', 'yes')
            ->expectsOutputToContain('2 succeeded, 0 failed, 0 skipped')
            ->assertExitCode(0);

        $this->assertSame(['deals.create', 'deals.create'], $this->api->endpoints());
    }

    public function test_production_requires_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $this->dealsCsv(1), '--write' => true])
                ->expectsOutputToContain('In production, writing needs --force')
                ->assertExitCode(1);

            $this->assertSame(0, $this->api->callCount());
        } finally {
            // Teardown rolls back the migrations, which asks for confirmation in production
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_no_interaction_requires_force(): void
    {
        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $this->dealsCsv(1), '--write' => true, '--no-interaction' => true])
            ->expectsOutputToContain('add --force')
            ->assertExitCode(1);
    }

    // -- import: behaviour ------------------------------------------------------

    public function test_a_dry_run_shows_the_bodies_and_sends_nothing(): void
    {
        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $this->dealsCsv(2), '--dry-run' => true])
            ->expectsOutputToContain('Dry run — nothing was sent. 2 rows valid, 0 invalid, 0 skipped.')
            ->expectsOutputToContain('line 2 POST deals.create')
            ->assertExitCode(0);

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_invalid_lines_are_listed_and_nothing_is_sent(): void
    {
        $file = $this->path('bad.csv');
        file_put_contents($file, "title,lead.customer.type,lead.customer.id\nGood,company,".self::COMPANY."\nNo lead,,\n");

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true, '--force' => true])
            ->expectsOutputToContain('1 of 2 rows are invalid. Nothing was sent.')
            ->expectsOutputToContain('line 3:')
            ->assertExitCode(1);

        $this->assertSame(0, $this->api->callCount());
    }

    public function test_csv_cells_become_nested_typed_values(): void
    {
        $file = $this->path('typed.csv');
        file_put_contents($file, "title,lead.customer.type,lead.customer.id,estimated_value.amount,estimated_value.currency,summary\n"
            .'Big,company,'.self::COMPANY.",1500.50,EUR,\n");

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true, '--force' => true, '--numeric' => 'estimated_value.amount'])
            ->assertExitCode(0);

        $body = $this->api->lastBody();
        $this->assertSame(['type' => 'company', 'id' => self::COMPANY], $body['lead']['customer']);
        $this->assertSame(1500.5, $body['estimated_value']['amount']);
        $this->assertArrayNotHasKey('summary', $body, 'An empty cell is left out');
    }

    public function test_a_failed_run_writes_a_results_file_that_resume_picks_up(): void
    {
        $file = $this->dealsCsv(3);
        $this->api->queueResponses([['data' => ['id' => 'd1']], ['error' => true, 'status_code' => 422, 'message' => 'Refused']]);

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true, '--force' => true])
            ->expectsOutputToContain('1 succeeded, 1 failed, 1 skipped')
            ->assertExitCode(1);

        $results = glob(storage_path('app/teamleader/import-deals-*.json'))[0];
        $this->assertSame([2], json_decode(file_get_contents($results), true)['succeeded'], 'Keyed by line number');

        $this->api->reset();

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--write' => true, '--force' => true, '--resume' => $results])
            ->expectsOutputToContain('2 succeeded, 0 failed, 1 skipped')
            ->assertExitCode(0);

        $this->assertSame(2, $this->api->callCount(), 'Line 2 is not sent again');
    }

    public function test_update_from_a_file(): void
    {
        $file = $this->path('update.jsonl');
        file_put_contents($file, json_encode(['id' => 'deal-1', 'title' => 'Renamed'])."\n");

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--update' => true, '--write' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame('deals.update', $this->api->lastEndpoint());
        $this->assertSame('deal-1', $this->api->lastBody()['id']);
    }

    public function test_method_calls_one_method_per_row(): void
    {
        $file = $this->path('win.csv');
        file_put_contents($file, "id\ndeal-1\ndeal-2\n");

        $this->artisan('teamleader:import', ['resource' => 'deals', 'file' => $file, '--method' => 'win', '--write' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(['deals.win', 'deals.win'], $this->api->endpoints());
    }

    // -- call -------------------------------------------------------------------

    public function test_call_reads_without_write(): void
    {
        $this->api->queueResponse(['data' => ['id' => 'u1', 'first_name' => 'Michael'], 'headers' => []]);

        $this->artisan('teamleader:call', ['endpoint' => 'users.me'])
            ->expectsOutputToContain('"first_name": "Michael"')
            ->assertExitCode(0);
    }

    public function test_call_to_a_writing_endpoint_needs_write(): void
    {
        $this->artisan('teamleader:call', ['endpoint' => 'companies.delete', '--data' => '{"id":"c1"}'])
            ->expectsOutputToContain('Nothing was sent')
            ->assertExitCode(1);

        $this->assertSame(0, $this->api->callCount());

        $this->artisan('teamleader:call', ['endpoint' => 'companies.delete', '--data' => '{"id":"c1"}', '--write' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(['id' => 'c1'], $this->api->lastBody());
    }

    public function test_call_refuses_invalid_json(): void
    {
        $this->artisan('teamleader:call', ['endpoint' => 'companies.info', '--data' => '{id:'])
            ->expectsOutputToContain('--data is not valid JSON')
            ->assertExitCode(2);
    }

    // -- helpers ----------------------------------------------------------------

    private function dealsCsv(int $rows): string
    {
        $file = $this->path("deals-{$rows}.csv");
        $lines = ['title,lead.customer.type,lead.customer.id'];

        for ($i = 1; $i <= $rows; $i++) {
            $lines[] = "Deal {$i},company,".self::COMPANY;
        }

        file_put_contents($file, implode("\n", $lines)."\n");

        return $file;
    }

    private function path(string $file): string
    {
        return $this->directory.'/'.$file;
    }
}
