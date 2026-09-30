<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use McoreServices\TeamleaderSDK\Connections\ConnectionConfig;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Events\RequestFailed;
use McoreServices\TeamleaderSDK\Exceptions\ConfigurationException;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\Services\ApiRateLimiterService;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use ReflectionProperty;
use Throwable;

/**
 * v3.0 (§7): several Teamleader accounts in one application, each with its own
 * credentials, tokens, cache entries, lock and rate-limit window.
 */
final class ConnectionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['teamleader.connections' => [
            'antwerp' => ['client_id' => 'antwerp-client', 'client_secret' => 'antwerp-secret'],
            'ghent' => ['client_id' => 'ghent-client', 'client_secret' => 'ghent-secret', 'redirect_uri' => 'https://ghent.example.test/callback'],
        ]]);

        app(ConnectionManager::class)->purge();
    }

    // -- resolution -------------------------------------------------------------

    public function test_the_flat_keys_are_the_default_connection(): void
    {
        $sdk = app(TeamleaderSDK::class);

        $this->assertSame('default', $sdk->connectionName());
        $this->assertSame('test_client_id', $sdk->getConnectionConfig()->clientId);
        $this->assertSame($sdk, Teamleader::connection());
    }

    public function test_a_named_connection_uses_its_own_credentials(): void
    {
        $antwerp = Teamleader::connection('antwerp');

        $this->assertSame('antwerp', $antwerp->connectionName());
        $this->assertSame('antwerp-client', $antwerp->getConnectionConfig()->clientId);
        $this->assertStringContainsString('client_id=antwerp-client', $antwerp->getAuthorizationUrl());
    }

    public function test_redirect_uri_falls_back_to_the_default_one(): void
    {
        $this->assertSame('http://localhost/callback', Teamleader::connection('antwerp')->getConnectionConfig()->redirectUri);
        $this->assertSame('https://ghent.example.test/callback', Teamleader::connection('ghent')->getConnectionConfig()->redirectUri);
    }

    public function test_each_connection_is_built_once(): void
    {
        $this->assertSame(Teamleader::connection('antwerp'), Teamleader::connection('antwerp'));
        $this->assertNotSame(Teamleader::connection('antwerp'), Teamleader::connection('ghent'));
    }

    public function test_a_connection_without_its_own_secret_fails_naming_it(): void
    {
        config(['teamleader.connections.bruges' => ['client_id' => 'bruges-client']]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Teamleader connection 'bruges' is missing: client_secret");

        Teamleader::connection('bruges');
    }

    public function test_an_unknown_connection_fails_naming_it(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Teamleader connection 'leuven' is not configured");

        Teamleader::connection('leuven');
    }

    public function test_the_default_connection_name_is_configurable(): void
    {
        config(['teamleader.default' => 'antwerp']);
        app(ConnectionManager::class)->purge();

        $this->assertSame('antwerp', app(ConnectionManager::class)->connection()->connectionName());
    }

    public function test_connections_can_be_defined_at_runtime(): void
    {
        Teamleader::extend('tenant-42', fn () => ['client_id' => 'tenant-client', 'client_secret' => 'tenant-secret']);

        $this->assertSame('tenant-client', Teamleader::connection('tenant-42')->getConnectionConfig()->clientId);
    }

    public function test_unknown_names_can_be_resolved_by_the_application(): void
    {
        Teamleader::resolveConnectionsUsing(fn (string $name) => str_starts_with($name, 'tenant-')
            ? ['client_id' => "{$name}-client", 'client_secret' => 'secret']
            : null);

        $this->assertSame('tenant-7-client', Teamleader::connection('tenant-7')->getConnectionConfig()->clientId);
    }

    public function test_names_lists_configured_connections(): void
    {
        $this->assertSame(['antwerp', 'default', 'ghent'], app(ConnectionManager::class)->names());
    }

    // -- isolation --------------------------------------------------------------

    public function test_tokens_never_cross_connections(): void
    {
        Teamleader::connection('antwerp')->getTokenService()->storeTokens(['access_token' => 'antwerp-token', 'refresh_token' => 'r', 'expires_in' => 3600]);

        $this->assertSame('antwerp-token', Teamleader::connection('antwerp')->getTokenService()->getValidAccessToken());
        $this->assertNull(Teamleader::connection('ghent')->getTokenService()->getValidAccessToken());
        $this->assertNull(Teamleader::connection()->getTokenService()->getValidAccessToken());
    }

    public function test_each_integration_has_its_own_rate_limit_window(): void
    {
        $antwerp = $this->limiterOf(Teamleader::connection('antwerp'))->windowKey();
        $ghent = $this->limiterOf(Teamleader::connection('ghent'))->windowKey();

        $this->assertNotSame($antwerp, $ghent);
        $this->assertStringNotContainsString('antwerp-client', $antwerp, 'The client ID is hashed, never stored');
    }

    public function test_resources_talk_to_the_connection_they_came_from(): void
    {
        $companies = Teamleader::connection('antwerp')->companies();

        $api = (new ReflectionProperty($companies, 'api'))->getValue($companies);

        $this->assertSame('antwerp', $api->connectionName());
    }

    public function test_a_refresh_uses_the_connections_own_credentials(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(['access_token' => 'new', 'refresh_token' => 'new-r', 'expires_in' => 3600]))]));
        $stack->push(Middleware::history($history));

        $credentials = ConnectionConfig::fromArray('antwerp', ['client_id' => 'antwerp-client', 'client_secret' => 'antwerp-secret', 'redirect_uri' => 'x']);
        $service = new TokenService(null, 'antwerp', $credentials);
        $service->storeTokens(['access_token' => 'old', 'refresh_token' => 'old-r', 'expires_in' => 60]);
        (new ReflectionProperty($service, 'httpClient'))->setValue($service, new Client(['handler' => $stack]));

        $this->assertSame('new', $service->refreshTokenIfNeeded());

        parse_str((string) $history[0]['request']->getBody(), $form);
        $this->assertSame('antwerp-client', $form['client_id']);
        $this->assertSame('antwerp-secret', $form['client_secret']);
    }

    public function test_events_carry_the_connection_name(): void
    {
        $fired = [];
        Event::listen(RequestFailed::class, function (RequestFailed $event) use (&$fired) {
            $fired[] = $event;
        });

        try {
            Teamleader::connection('ghent')->request('POST', 'companies.list');
        } catch (Throwable) {
            // No token: fails, as it should
        }

        $this->assertSame('ghent', $fired[0]->connection);
    }

    private function limiterOf(TeamleaderSDK $sdk): ApiRateLimiterService
    {
        return (new ReflectionProperty($sdk, 'rateLimiter'))->getValue($sdk);
    }
}
