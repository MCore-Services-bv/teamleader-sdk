<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Connections\DatabaseConnectionStore;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Tokens\StoredTokens;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;

/**
 * v3.0 (§7.1): connections whose credentials live in the database, managed
 * with teamleader:connections:add / list / remove — so adding an account
 * needs no deploy.
 */
final class DatabaseConnectionsTest extends TestCase
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

    // -- the store --------------------------------------------------------------

    public function test_credentials_are_encrypted_in_the_table(): void
    {
        $this->store->put('bruges', ['client_id' => 'bruges-client', 'client_secret' => 'bruges-secret']);

        $row = DB::table('teamleader_connections')->where('name', 'bruges')->first();

        $this->assertStringNotContainsString('bruges-secret', $row->client_secret);
        $this->assertSame('bruges-secret', Crypt::decryptString($row->client_secret));
        $this->assertSame('bruges-client', $this->store->get('bruges')['client_id']);
    }

    public function test_an_invalid_name_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->store->put('bad name!', ['client_id' => 'x', 'client_secret' => 'y']);
    }

    public function test_reads_tolerate_a_missing_table(): void
    {
        Schema::drop('teamleader_connections');

        $this->assertNull($this->store->get('bruges'));
        $this->assertSame([], $this->store->names());
        $this->assertSame('antwerp-client', Teamleader::connection('antwerp')->getConnectionConfig()->clientId);
    }

    // -- the manager ------------------------------------------------------------

    public function test_a_stored_connection_is_resolved(): void
    {
        $this->store->put('bruges', ['client_id' => 'bruges-client', 'client_secret' => 'bruges-secret']);

        $bruges = Teamleader::connection('bruges');

        $this->assertSame('bruges-client', $bruges->getConnectionConfig()->clientId);
        $this->assertSame('http://localhost/callback', $bruges->getConnectionConfig()->redirectUri, 'falls back to the default redirect URI');
        $this->assertSame('database', app(ConnectionManager::class)->sourceOf('bruges'));
        $this->assertContains('bruges', app(ConnectionManager::class)->names());
    }

    public function test_configuration_wins_over_the_database(): void
    {
        $this->store->put('antwerp', ['client_id' => 'from-database', 'client_secret' => 'x']);

        $this->assertSame('antwerp-client', Teamleader::connection('antwerp')->getConnectionConfig()->clientId);
        $this->assertSame('config', app(ConnectionManager::class)->sourceOf('antwerp'));
    }

    // -- commands ---------------------------------------------------------------

    public function test_add_prompts_for_the_credentials_and_stores_them(): void
    {
        $this->artisan('teamleader:connections:add', ['name' => 'bruges'])
            ->expectsQuestion('Client ID', 'bruges-client')
            ->expectsQuestion('Client secret', 'bruges-secret')
            ->expectsOutputToContain("Connection 'bruges' stored.")
            ->assertExitCode(0);

        $this->assertSame('bruges-secret', $this->store->get('bruges')['client_secret']);
        $this->assertSame('bruges-client', Teamleader::connection('bruges')->getConnectionConfig()->clientId);
    }

    public function test_add_takes_options_for_scripts(): void
    {
        $this->artisan('teamleader:connections:add', [
            'name' => 'bruges',
            '--client-id' => 'bruges-client',
            '--client-secret' => 'bruges-secret',
            '--expected-account' => 'account-bruges',
        ])->assertExitCode(0);

        $this->assertSame('account-bruges', Teamleader::connection('bruges')->getConnectionConfig()->expectedAccountId);
    }

    public function test_add_refuses_a_name_defined_in_config(): void
    {
        $this->artisan('teamleader:connections:add', ['name' => 'antwerp', '--client-id' => 'x', '--client-secret' => 'y'])
            ->expectsOutputToContain('defined in config/teamleader.php')
            ->assertExitCode(1);

        $this->assertFalse($this->store->has('antwerp'));
    }

    public function test_add_asks_before_replacing_stored_credentials(): void
    {
        $this->store->put('bruges', ['client_id' => 'old', 'client_secret' => 'old']);

        $this->artisan('teamleader:connections:add', ['name' => 'bruges', '--client-id' => 'new', '--client-secret' => 'new'])
            ->expectsConfirmation("Connection 'bruges' is already stored. Replace its credentials?", 'no')
            ->assertExitCode(1);

        $this->assertSame('old', $this->store->get('bruges')['client_id']);
    }

    public function test_list_shows_where_each_connection_is_defined(): void
    {
        $this->store->put('bruges', ['client_id' => 'bruges-client', 'client_secret' => 'x']);

        // Each expectation consumes one output line: the bruges row is the one
        // saying `database`, the antwerp and default rows say `config`
        $this->artisan('teamleader:connections:list')
            ->expectsOutputToContain('database')
            ->expectsOutputToContain('config')
            ->assertExitCode(0);

        $this->assertSame('database', app(ConnectionManager::class)->sourceOf('bruges'));
    }

    public function test_remove_deletes_the_credentials_and_the_tokens(): void
    {
        $this->store->put('bruges', ['client_id' => 'bruges-client', 'client_secret' => 'x']);
        app(TokenStore::class)->put('bruges', new StoredTokens('access', 'refresh', CarbonImmutable::now()->addHour()));

        $this->artisan('teamleader:connections:remove', ['name' => 'bruges'])
            ->expectsConfirmation("Remove connection 'bruges' and its tokens? The account must then be connected again to be used.", 'yes')
            ->assertExitCode(0);

        $this->assertFalse($this->store->has('bruges'));
        $this->assertNull(app(TokenStore::class)->get('bruges'));
    }

    public function test_remove_refuses_a_connection_defined_in_config(): void
    {
        $this->artisan('teamleader:connections:remove', ['name' => 'antwerp', '--force' => true])
            ->expectsOutputToContain('Remove it there')
            ->assertExitCode(1);
    }

    // -- add without a shared redirect URI --------------------------------------

    public function test_add_asks_for_the_redirect_uri_when_none_is_shared(): void
    {
        config(['teamleader.redirect_uri' => null]);

        $this->artisan('teamleader:connections:add', ['name' => 'bruges'])
            ->expectsQuestion('Client ID', 'bruges-client')
            ->expectsQuestion('Client secret', 'bruges-secret')
            ->expectsQuestion('Redirect URI (as registered in the integration)', 'https://bruges.test/teamleader/callback')
            ->expectsOutputToContain("Connection 'bruges' stored.")
            ->assertExitCode(0);

        $this->assertSame('https://bruges.test/teamleader/callback', $this->store->get('bruges')['redirect_uri']);
        $this->assertSame(
            'https://bruges.test/teamleader/callback',
            Teamleader::connection('bruges')->getConnectionConfig()->redirectUri
        );
    }

    public function test_an_invalid_redirect_uri_stores_nothing(): void
    {
        config(['teamleader.redirect_uri' => null]);

        $this->artisan('teamleader:connections:add', [
            'name' => 'bruges',
            '--client-id' => 'bruges-client',
            '--client-secret' => 'bruges-secret',
            '--redirect-uri' => 'teamleader/callback',
        ])
            ->expectsOutputToContain('is not a full URL')
            ->assertExitCode(1);

        $this->assertFalse($this->store->has('bruges'));
    }

    public function test_without_any_redirect_uri_a_non_interactive_run_fails_and_stores_nothing(): void
    {
        config(['teamleader.redirect_uri' => null]);

        $this->artisan('teamleader:connections:add', [
            'name' => 'bruges',
            '--client-id' => 'bruges-client',
            '--client-secret' => 'bruges-secret',
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('No redirect URI')
            ->assertExitCode(1);

        $this->assertFalse($this->store->has('bruges'));
    }

    public function test_the_default_connection_can_live_in_the_database(): void
    {
        config(['teamleader.client_id' => null, 'teamleader.client_secret' => null, 'teamleader.redirect_uri' => null]);
        app(ConnectionManager::class)->purge();

        $this->artisan('teamleader:connections:add', [
            'name' => 'default',
            '--client-id' => 'db-client',
            '--client-secret' => 'db-secret',
            '--redirect-uri' => 'https://app.test/teamleader/callback',
        ])->assertExitCode(0);

        $this->assertSame('database', app(ConnectionManager::class)->sourceOf('default'));
        $this->assertSame('db-client', Teamleader::connection('default')->getConnectionConfig()->clientId);
    }

    public function test_add_suggests_making_a_named_connection_the_default(): void
    {
        $this->artisan('teamleader:connections:add', [
            'name' => 'bruges',
            '--client-id' => 'bruges-client',
            '--client-secret' => 'bruges-secret',
        ])
            ->expectsOutputToContain('TEAMLEADER_CONNECTION=bruges')
            ->assertExitCode(0);
    }

    public function test_a_named_connection_works_through_the_facade_without_a_default(): void
    {
        // An application with only stored, named connections: nothing in .env
        config(['teamleader.client_id' => null, 'teamleader.client_secret' => null, 'teamleader.redirect_uri' => null]);
        app(ConnectionManager::class)->purge();
        app()->forgetInstance(TeamleaderSDK::class);
        Teamleader::clearResolvedInstances();

        $this->store->put('bruges', [
            'client_id' => 'bruges-client',
            'client_secret' => 'bruges-secret',
            'redirect_uri' => 'https://bruges.test/teamleader/callback',
        ]);

        $this->assertSame('bruges-client', Teamleader::connection('bruges')->getConnectionConfig()->clientId);
    }
}
