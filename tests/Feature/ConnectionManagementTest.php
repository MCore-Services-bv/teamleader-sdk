<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\Services\ConfigurationValidator;
use McoreServices\TeamleaderSDK\Services\HealthCheckService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Tokens\StoredTokens;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;

/**
 * v3.2: managing connections after the first setup — renaming one, setting the
 * expected account once it is known, seeing account ids — and applications
 * that only have named connections, no flat `default`.
 */
final class ConnectionManagementTest extends TestCase
{
    private DatabaseConnectionStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        config(['teamleader.connections' => [
            'antwerp' => ['client_id' => 'antwerp-client', 'client_secret' => 'antwerp-secret'],
        ]]);

        $this->store = app(DatabaseConnectionStore::class);
        app(ConnectionManager::class)->purge();
    }

    private function storeConnection(string $name, ?string $expected = null): void
    {
        $this->store->put($name, [
            'client_id' => "{$name}-client",
            'client_secret' => "{$name}-secret",
            'redirect_uri' => 'https://app.test/teamleader/callback',
            'expected_account_id' => $expected,
        ]);
    }

    private function connect(string $name, ?string $accountId = null): void
    {
        app(TokenStore::class)->put($name, new StoredTokens(
            accessToken: "{$name}-access",
            refreshToken: "{$name}-refresh",
            expiresAt: CarbonImmutable::now()->addHour(),
            accountId: $accountId,
            accountName: $accountId ? "Account {$accountId}" : null,
        ));
    }

    /** No flat TEAMLEADER_CLIENT_ID: only the connections the test stores */
    private function withoutDefault(): void
    {
        config([
            'teamleader.client_id' => null,
            'teamleader.client_secret' => null,
            'teamleader.redirect_uri' => null,
            'teamleader.connections' => [],
        ]);

        app(ConnectionManager::class)->purge();
        app()->forgetInstance(TeamleaderSDK::class);
        Teamleader::clearResolvedInstances();
    }

    // -- rename -------------------------------------------------------------------

    public function test_rename_moves_the_credentials_and_the_tokens(): void
    {
        $this->storeConnection('brugse');
        $this->connect('brugse', 'account-bruges');

        $this->artisan('teamleader:connections:rename', ['from' => 'brugse', 'to' => 'bruges'])
            ->expectsOutputToContain("Connection 'brugse' is now 'bruges', and still connected.")
            ->assertExitCode(0);

        $this->assertFalse($this->store->has('brugse'));
        $this->assertSame('brugse-client', $this->store->get('bruges')['client_id']);
        $this->assertNull(app(TokenStore::class)->get('brugse'));
        $this->assertSame('account-bruges', app(TokenStore::class)->get('bruges')->accountId);
        $this->assertSame('brugse-access', Teamleader::connection('bruges')->getTokenService()->getValidAccessToken());
    }

    public function test_rename_refuses_a_connection_defined_in_config_unless_only_tokens_move(): void
    {
        $this->connect('antwerpen', 'account-antwerp');

        $this->artisan('teamleader:connections:rename', ['from' => 'antwerp', 'to' => 'antwerpen'])
            ->expectsOutputToContain('Rename it there, then run this command with --tokens-only')
            ->assertExitCode(1);

        // Renamed in config from 'antwerpen' to 'antwerp': move its tokens along
        $this->artisan('teamleader:connections:rename', ['from' => 'antwerpen', 'to' => 'antwerp', '--tokens-only' => true])
            ->assertExitCode(0);

        $this->assertSame('account-antwerp', app(TokenStore::class)->get('antwerp')->accountId);
        $this->assertNull(app(TokenStore::class)->get('antwerpen'));
    }

    public function test_rename_refuses_a_name_that_exists(): void
    {
        $this->storeConnection('bruges');
        $this->storeConnection('ghent');

        $this->artisan('teamleader:connections:rename', ['from' => 'bruges', 'to' => 'ghent'])
            ->expectsOutputToContain("'ghent' already exists")
            ->assertExitCode(1);

        $this->assertTrue($this->store->has('bruges'));
    }

    public function test_rename_waits_for_a_running_token_refresh(): void
    {
        $this->storeConnection('bruges');
        Cache::add('teamleader:bruges:refresh_lock', true, 60);

        $this->artisan('teamleader:connections:rename', ['from' => 'bruges', 'to' => 'brugge'])
            ->expectsOutputToContain('A token refresh for \'bruges\' is running')
            ->assertExitCode(1);

        $this->assertTrue($this->store->has('bruges'));
    }

    public function test_rename_refuses_an_invalid_name(): void
    {
        $this->storeConnection('bruges');

        $this->artisan('teamleader:connections:rename', ['from' => 'bruges', 'to' => 'brug ge'])
            ->assertExitCode(2);
    }

    // -- expect -------------------------------------------------------------------

    public function test_expect_current_takes_the_connected_account(): void
    {
        $this->storeConnection('bruges');
        $this->connect('bruges', 'account-bruges');

        $this->artisan('teamleader:connections:expect', ['name' => 'bruges', '--current' => true])
            ->assertExitCode(0);

        $this->assertSame('account-bruges', $this->store->get('bruges')['expected_account_id']);
        $this->assertSame('bruges-client', $this->store->get('bruges')['client_id']);
        $this->assertSame('account-bruges', Teamleader::connection('bruges')->getConnectionConfig()->expectedAccountId);
    }

    public function test_expect_all_current_sets_every_stored_connection(): void
    {
        foreach (['bruges', 'ghent'] as $name) {
            $this->storeConnection($name);
            $this->connect($name, "account-{$name}");
        }

        $this->artisan('teamleader:connections:expect', ['--all' => true, '--current' => true])
            ->assertExitCode(0);

        $this->assertSame('account-bruges', $this->store->get('bruges')['expected_account_id']);
        $this->assertSame('account-ghent', $this->store->get('ghent')['expected_account_id']);
    }

    public function test_expect_takes_an_id_and_clear_removes_it(): void
    {
        $this->storeConnection('bruges');

        $this->artisan('teamleader:connections:expect', ['name' => 'bruges', 'account' => 'account-x'])->assertExitCode(0);
        $this->assertSame('account-x', $this->store->get('bruges')['expected_account_id']);

        $this->artisan('teamleader:connections:expect', ['name' => 'bruges', '--clear' => true])->assertExitCode(0);
        $this->assertNull($this->store->get('bruges')['expected_account_id']);
    }

    public function test_expect_current_needs_a_known_account(): void
    {
        $this->storeConnection('bruges');

        $this->artisan('teamleader:connections:expect', ['name' => 'bruges', '--current' => true])
            ->expectsOutputToContain('account unknown')
            ->assertExitCode(1);

        $this->assertNull($this->store->get('bruges')['expected_account_id']);
    }

    public function test_expect_points_a_config_connection_to_its_config(): void
    {
        $this->connect('antwerp', 'account-antwerp');

        $this->artisan('teamleader:connections:expect', ['name' => 'antwerp', '--current' => true])
            ->expectsOutputToContain('set expected_account_id in config/teamleader.php')
            ->assertExitCode(1);
    }

    public function test_expect_needs_exactly_one_source(): void
    {
        $this->storeConnection('bruges');

        $this->artisan('teamleader:connections:expect', ['name' => 'bruges'])
            ->expectsOutputToContain('Give exactly one of')
            ->assertExitCode(2);
    }

    // -- account ids ----------------------------------------------------------------

    public function test_status_all_and_connections_list_show_the_account_id(): void
    {
        $this->storeConnection('bruges', 'account-bruges');
        $this->connect('bruges', 'account-bruges');

        $this->artisan('teamleader:status', ['--all' => true])
            ->expectsOutputToContain('account-bruges')
            ->assertExitCode(0);

        $this->artisan('teamleader:connections:list')
            ->expectsOutputToContain('account-bruges')
            ->assertExitCode(0);
    }

    // -- only named connections ------------------------------------------------------

    public function test_status_without_a_default_shows_every_connection(): void
    {
        $this->withoutDefault();
        $this->storeConnection('bruges');
        $this->connect('bruges', 'account-bruges');

        $this->artisan('teamleader:status')
            ->expectsOutputToContain("No 'default' connection; showing every connection.")
            ->assertExitCode(0);
    }

    public function test_data_commands_without_a_default_say_what_to_pass(): void
    {
        $this->withoutDefault();
        $this->storeConnection('bruges');

        $this->artisan('teamleader:list', ['resource' => 'companies'])
            ->expectsOutputToContain("There is no 'default' Teamleader connection. Pass --connection=<name> (configured: bruges)")
            ->assertExitCode(2);
    }

    public function test_health_without_a_default_skips_the_per_connection_checks(): void
    {
        $this->withoutDefault();
        $this->storeConnection('bruges');
        $this->connect('bruges', 'account-bruges');

        $checks = app(HealthCheckService::class)->check()->getChecks();

        $this->assertSame('skipped', $checks['authentication']['status']);
        $this->assertSame('skipped', $checks['api_connectivity']['status']);
        $this->assertSame('healthy', $checks['connections']['status']);
    }

    public function test_the_validator_accepts_only_named_connections(): void
    {
        $this->withoutDefault();
        $this->storeConnection('bruges');

        $result = (new ConfigurationValidator)->validate();

        $this->assertSame([], array_values(array_filter($result->errors, fn (string $e) => str_contains($e, 'TEAMLEADER_CLIENT_ID'))));
        $this->assertSame([], array_values(array_filter($result->errors, fn (string $e) => str_contains($e, 'Redirect URI'))));
    }

    public function test_the_validator_reports_an_incomplete_named_connection(): void
    {
        $this->withoutDefault();
        $this->store->put('bruges', ['client_id' => 'bruges-client', 'client_secret' => 'bruges-secret']);

        $result = (new ConfigurationValidator)->validate();

        $this->assertContains(
            "Teamleader connection 'bruges' is incomplete: it needs a client_id, a client_secret and a redirect_uri",
            $result->errors
        );
    }
}
