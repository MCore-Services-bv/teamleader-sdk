<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Events\TokenRefreshFailed;
use McoreServices\TeamleaderSDK\Exceptions\ConnectionNeedsReauthorizationException;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\Services\HealthCheckService;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Tokens\StoredTokens;
use McoreServices\TeamleaderSDK\Tokens\TokenStore;
use ReflectionProperty;

/**
 * v3.0 (§7.5): tokens are renewed on a schedule, a refused refresh token marks
 * the connection instead of deleting it, and every surface — requests, the
 * status table, the health check — says which connection needs attention.
 */
final class TokenRenewalTest extends TestCase
{
    /** @var array<string, list<array<string, mixed>>> connection => HTTP history */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['teamleader.connections' => [
            'antwerp' => ['client_id' => 'antwerp-client', 'client_secret' => 'antwerp-secret'],
            'ghent' => ['client_id' => 'ghent-client', 'client_secret' => 'ghent-secret'],
        ]]);

        app(ConnectionManager::class)->purge();
        Cache::flush();
    }

    // -- refreshIfDue -----------------------------------------------------------

    public function test_a_token_that_is_not_due_is_left_alone(): void
    {
        $this->answerRefresh('antwerp', []);
        $this->connect('antwerp', expiresIn: 3600);

        $this->assertSame(TokenService::NOT_DUE, $this->tokens('antwerp')->refreshIfDue(1800));
        $this->assertSame([], $this->history['antwerp']);
    }

    public function test_a_due_token_is_refreshed_and_the_time_recorded(): void
    {
        $this->answerRefresh('antwerp', [$this->tokenResponse()]);
        $this->connect('antwerp', expiresIn: 600);

        $this->assertSame(TokenService::REFRESHED, $this->tokens('antwerp')->refreshIfDue(1800));

        $stored = app(TokenStore::class)->get('antwerp');
        $this->assertSame('new-access', $stored->accessToken);
        $this->assertNotNull($stored->lastRefreshedAt);
    }

    public function test_force_refreshes_a_token_that_is_not_due(): void
    {
        $this->answerRefresh('antwerp', [$this->tokenResponse()]);
        $this->connect('antwerp', expiresIn: 3600);

        $this->assertSame(TokenService::REFRESHED, $this->tokens('antwerp')->refreshIfDue(1800, force: true));
    }

    public function test_a_connection_without_tokens_is_not_connected(): void
    {
        $this->assertSame(TokenService::NOT_CONNECTED, $this->tokens('ghent')->refreshIfDue(1800));
    }

    // -- a refused refresh token ------------------------------------------------

    public function test_a_refused_refresh_token_marks_the_connection_and_keeps_the_tokens(): void
    {
        $failed = [];
        Event::listen(TokenRefreshFailed::class, function (TokenRefreshFailed $e) use (&$failed) {
            $failed[] = $e;
        });

        $this->answerRefresh('antwerp', [new Response(400, [], '{"error":"invalid_grant"}')]);
        $this->connect('antwerp', expiresIn: 600);

        $this->assertSame(TokenService::NEEDS_REAUTHORIZATION, $this->tokens('antwerp')->refreshIfDue(1800));

        $stored = app(TokenStore::class)->get('antwerp');
        $this->assertNotNull($stored, 'Kept for inspection, not deleted');
        $this->assertSame(StoredTokens::NEEDS_REAUTHORIZATION, $stored->status);
        $this->assertTrue($failed[0]->reauthorizationRequired);
        $this->assertSame('antwerp', $failed[0]->connection);
    }

    public function test_a_request_on_such_a_connection_throws_naming_it_and_sends_nothing(): void
    {
        config(['teamleader.error_handling.throw_exceptions' => false]);
        $this->connect('antwerp', expiresIn: 600, status: StoredTokens::NEEDS_REAUTHORIZATION);

        try {
            Teamleader::connection('antwerp')->companies()->list();
            $this->fail('Expected ConnectionNeedsReauthorizationException, whatever throw_exceptions says.');
        } catch (ConnectionNeedsReauthorizationException $e) {
            $this->assertSame('antwerp', $e->connection);
            $this->assertStringContainsString("'antwerp' needs to be connected again", $e->getMessage());
        }
    }

    public function test_connecting_again_clears_the_flag(): void
    {
        $this->connect('antwerp', expiresIn: 600, status: StoredTokens::NEEDS_REAUTHORIZATION);

        $this->tokens('antwerp')->storeTokens(['access_token' => 'fresh', 'refresh_token' => 'fresh-r', 'expires_in' => 3600]);

        $this->assertFalse($this->tokens('antwerp')->needsReauthorization());
        $this->assertSame('fresh', $this->tokens('antwerp')->getValidAccessToken());
    }

    // -- the command ------------------------------------------------------------

    public function test_the_command_refreshes_what_is_due(): void
    {
        $this->answerRefresh('antwerp', [$this->tokenResponse()]);
        $this->answerRefresh('ghent', []);
        $this->connect('antwerp', expiresIn: 600);
        $this->connect('ghent', expiresIn: 3600);

        $this->artisan('teamleader:tokens:refresh')
            ->expectsOutputToContain('refreshed')
            ->expectsOutputToContain('not due')
            ->assertExitCode(0);

        $this->assertCount(1, $this->history['antwerp']);
        $this->assertSame([], $this->history['ghent']);
    }

    public function test_the_command_fails_when_a_connection_needs_reauthorization(): void
    {
        $this->answerRefresh('antwerp', [new Response(401, [], '{"error":"invalid_grant"}')]);
        $this->connect('antwerp', expiresIn: 600);

        $this->artisan('teamleader:tokens:refresh', ['--connection' => ['antwerp']])
            ->expectsOutputToContain('needs reauthorization')
            ->assertExitCode(1);
    }

    public function test_the_command_records_the_account_of_a_connection_upgraded_from_2x(): void
    {
        // A 2.x token table has no account: the refresh run looks it up once
        $this->answerRefresh('antwerp', []);
        $this->connect('antwerp', expiresIn: 3600, accountId: null);

        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['data' => ['account' => ['type' => 'account', 'id' => 'account-antwerp']]])),
            new Response(200, [], json_encode(['data' => [['id' => 'dep-1', 'name' => 'Klant Antwerpen BV']]])),
        ]));
        $stack->push(Middleware::history($history));
        (new ReflectionProperty(Teamleader::connection('antwerp'), 'client'))
            ->setValue(Teamleader::connection('antwerp'), new Client(['handler' => $stack]));

        $this->artisan('teamleader:tokens:refresh', ['--connection' => ['antwerp']])->assertExitCode(0);

        $info = $this->tokens('antwerp')->getTokenInfo();
        $this->assertSame('account-antwerp', $info['account_id']);
        $this->assertSame('Klant Antwerpen BV', $info['account_name']);
        $this->assertSame('antwerp-access', $this->tokens('antwerp')->getValidAccessToken());
        $this->assertCount(2, $history);

        // Known from now on: the next run asks nothing
        $this->artisan('teamleader:tokens:refresh', ['--connection' => ['antwerp']])->assertExitCode(0);
        $this->assertCount(2, $history);
    }

    public function test_the_refresh_is_scheduled(): void
    {
        $commands = array_map(fn ($event) => $event->command, app(Schedule::class)->events());

        $this->assertNotEmpty(array_filter($commands, fn ($command) => str_contains((string) $command, 'teamleader:tokens:refresh')));
    }

    // -- visibility -------------------------------------------------------------

    public function test_status_all_lists_every_connection(): void
    {
        $this->connect('antwerp', expiresIn: 3600, accountName: 'Klant Antwerpen');
        $this->connect('ghent', expiresIn: 600, status: StoredTokens::NEEDS_REAUTHORIZATION);

        $this->artisan('teamleader:status', ['--all' => true])
            ->expectsOutputToContain('Klant Antwerpen')
            ->expectsOutputToContain('needs_reauthorization')
            ->assertExitCode(1);
    }

    public function test_the_health_check_reports_connections_needing_attention(): void
    {
        $this->connect('antwerp', expiresIn: -60);
        $this->connect('ghent', expiresIn: 600, status: StoredTokens::NEEDS_REAUTHORIZATION);

        $check = app(HealthCheckService::class)->check()->getChecks()['connections'];

        $this->assertSame('error', $check['status']);
        $this->assertStringContainsString('ghent', $check['details']['error']);
        $this->assertStringContainsString('antwerp', $check['details']['warning']);
        $this->assertStringContainsString('scheduler', $check['details']['warning']);
    }

    // -- helpers ----------------------------------------------------------------

    private function tokens(string $connection): TokenService
    {
        return Teamleader::connection($connection)->getTokenService();
    }

    /**
     * Store tokens directly. Call answerRefresh() first for a connection whose
     * token is due: building its SDK checks the token, and would otherwise
     * refresh it against the real Teamleader.
     */
    private function connect(
        string $connection,
        int $expiresIn,
        string $status = StoredTokens::CONNECTED,
        ?string $accountName = null,
        ?string $accountId = 'account-known',
    ): void {
        // An account id by default: without one, the refresh command looks the
        // account up, which would reach the real Teamleader
        app(TokenStore::class)->put($connection, new StoredTokens(
            accessToken: "{$connection}-access",
            refreshToken: "{$connection}-refresh",
            expiresAt: CarbonImmutable::now()->addSeconds($expiresIn),
            status: $status,
            accountId: $accountId,
            accountName: $accountName,
        ));
    }

    /**
     * @param  list<Response>  $responses
     */
    private function answerRefresh(string $connection, array $responses): void
    {
        $this->history[$connection] = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history[$connection]));

        $service = $this->tokens($connection);
        (new ReflectionProperty($service, 'httpClient'))->setValue($service, new Client(['handler' => $stack]));
    }

    private function tokenResponse(): Response
    {
        return new Response(200, [], json_encode(['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600]));
    }
}
