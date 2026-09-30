<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use McoreServices\TeamleaderSDK\Connections\ConnectionManager;
use McoreServices\TeamleaderSDK\Events\ConnectionAuthorized;
use McoreServices\TeamleaderSDK\Exceptions\AccountMismatchException;
use McoreServices\TeamleaderSDK\Exceptions\OAuthStateException;
use McoreServices\TeamleaderSDK\Facades\Teamleader;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use ReflectionProperty;

/**
 * v3.0 (B4, §7.4): the OAuth state is generated and checked by the SDK, one
 * callback route serves every connection, and the connected account is
 * identified and — when configured — checked.
 */
final class OAuthFlowTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['teamleader.connections' => [
            'antwerp' => ['client_id' => 'antwerp-client', 'client_secret' => 'antwerp-secret', 'expected_account_id' => 'account-antwerp'],
            'ghent' => ['client_id' => 'ghent-client', 'client_secret' => 'ghent-secret'],
        ]]);

        app(ConnectionManager::class)->purge();
    }

    // -- state ------------------------------------------------------------------

    public function test_authorize_generates_a_state_and_remembers_the_connection(): void
    {
        $url = Teamleader::connection('ghent')->getAuthorizationUrl();

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(40, strlen($query['state']));
        $this->assertSame([$query['state'] => 'ghent'], session(TeamleaderSDK::OAUTH_STATE_SESSION_KEY));
    }

    public function test_a_state_passed_in_is_used_as_it_is_and_not_stored(): void
    {
        $url = Teamleader::getAuthorizationUrl('my-own-state');

        $this->assertStringContainsString('state=my-own-state', $url);
        $this->assertNull(session(TeamleaderSDK::OAUTH_STATE_SESSION_KEY));
    }

    public function test_the_callback_is_routed_to_the_connection_that_started_it(): void
    {
        $state = $this->stateFor('ghent');
        $this->fakeTeamleader('ghent', 'account-ghent', 'Klant Gent');

        $connected = Teamleader::handleCallback('the-code', $state);

        $this->assertInstanceOf(TeamleaderSDK::class, $connected);
        $this->assertSame('ghent', $connected->connectionName());
        $this->assertSame('ghent-client', $this->form(0)['client_id']);

        $info = Teamleader::connection('ghent')->getTokenService()->getTokenInfo();
        $this->assertTrue($info['has_access_token']);
        $this->assertSame('account-ghent', $info['account_id']);
        $this->assertSame('Klant Gent', $info['account_name']);

        $this->assertFalse(Teamleader::connection()->getTokenService()->getTokenInfo()['has_access_token'], 'default untouched');
    }

    public function test_a_state_is_used_once(): void
    {
        $state = $this->stateFor('ghent');
        $this->stateFor('ghent'); // a second tab keeps the session non-empty
        $this->fakeTeamleader('ghent', 'account-ghent', 'Klant Gent');

        Teamleader::handleCallback('the-code', $state);

        $this->expectException(OAuthStateException::class);

        Teamleader::handleCallback('the-code', $state);
    }

    public function test_a_forged_state_is_refused_and_nothing_is_sent(): void
    {
        $this->stateFor('ghent');
        $this->fakeTeamleader('ghent', 'account-ghent', 'Klant Gent');

        try {
            Teamleader::handleCallback('attacker-code', 'not-a-state-we-issued');
            $this->fail('A forged state must be refused.');
        } catch (OAuthStateException) {
        }

        $this->assertSame([], $this->history);
    }

    public function test_without_a_pending_state_the_2x_flow_still_works(): void
    {
        $this->fakeTeamleader('default', 'account-default', 'MCore Services');

        $this->assertInstanceOf(TeamleaderSDK::class, Teamleader::handleCallback('the-code', 'caller-managed'));
    }

    // -- expected account -------------------------------------------------------

    public function test_the_expected_account_is_accepted(): void
    {
        $state = $this->stateFor('antwerp');
        $this->fakeTeamleader('antwerp', 'account-antwerp', 'Klant Antwerpen');

        $this->assertSame('antwerp', Teamleader::handleCallback('the-code', $state)->connectionName());
    }

    public function test_another_account_is_refused_and_nothing_is_stored(): void
    {
        $state = $this->stateFor('antwerp');
        $this->fakeTeamleader('antwerp', 'account-SOMEONE-ELSE', 'Wrong Company');

        try {
            Teamleader::handleCallback('the-code', $state);
            $this->fail('A different account must be refused.');
        } catch (AccountMismatchException $e) {
            $this->assertSame('account-antwerp', $e->expectedAccountId);
            $this->assertSame('account-SOMEONE-ELSE', $e->actualAccountId);
        }

        $this->assertFalse(Teamleader::connection('antwerp')->getTokenService()->getTokenInfo()['has_access_token']);
    }

    public function test_an_account_that_cannot_be_identified_is_refused_when_one_is_expected(): void
    {
        $state = $this->stateFor('antwerp');
        $this->fakeResponses('antwerp', [$this->tokenResponse(), new Response(500)]);

        $this->expectException(AccountMismatchException::class);
        $this->expectExceptionMessage('could not be identified');

        Teamleader::handleCallback('the-code', $state);
    }

    // -- events -----------------------------------------------------------------

    public function test_connection_authorized_is_fired(): void
    {
        $fired = [];
        Event::listen(ConnectionAuthorized::class, function (ConnectionAuthorized $e) use (&$fired) {
            $fired[] = $e;
        });

        $state = $this->stateFor('ghent');
        $this->fakeTeamleader('ghent', 'account-ghent', 'Klant Gent');

        Teamleader::handleCallback('the-code', $state);

        $this->assertCount(1, $fired);
        $this->assertSame(['ghent', 'account-ghent', 'Klant Gent'], [$fired[0]->connection, $fired[0]->accountId, $fired[0]->accountName]);
    }

    // -- helpers ----------------------------------------------------------------

    private function stateFor(string $connection): string
    {
        parse_str((string) parse_url(Teamleader::connection($connection)->getAuthorizationUrl(), PHP_URL_QUERY), $query);

        return $query['state'];
    }

    private function fakeTeamleader(string $connection, string $accountId, string $accountName): void
    {
        $this->fakeResponses($connection, [
            $this->tokenResponse(),
            new Response(200, [], json_encode(['data' => ['id' => 'user-1', 'account' => ['type' => 'account', 'id' => $accountId]]])),
            new Response(200, [], json_encode(['data' => [['id' => 'dep-1', 'name' => $accountName]]])),
        ]);
    }

    /**
     * @param  list<Response>  $responses
     */
    private function fakeResponses(string $connection, array $responses): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $sdk = Teamleader::connection($connection);
        (new ReflectionProperty($sdk, 'client'))->setValue($sdk, new Client(['handler' => $stack, 'http_errors' => false]));
    }

    private function tokenResponse(): Response
    {
        return new Response(200, [], json_encode(['access_token' => 'access', 'refresh_token' => 'refresh', 'expires_in' => 3600, 'token_type' => 'Bearer']));
    }

    /**
     * @return array<string, string>
     */
    private function form(int $index): array
    {
        parse_str((string) $this->history[$index]['request']->getBody(), $form);

        return $form;
    }
}
