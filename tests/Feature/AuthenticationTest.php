<?php

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\TeamleaderSDK;
use Illuminate\Support\Facades\Cache;

class AuthenticationTest extends TestCase
{
    private TeamleaderSDK $sdk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sdk = new TeamleaderSDK();
        Cache::flush();
    }

    public function testGeneratesAuthorizationUrl(): void
    {
        $url = $this->sdk->getAuthorizationUrl('test_state');

        $this->assertStringContainsString('oauth2/authorize', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('state=test_state', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testChecksAuthenticationStatus(): void
    {
        $this->assertFalse($this->sdk->isAuthenticated());
    }

    public function testCanSetAccessTokenManually(): void
    {
        $token = 'test_access_token_12345';

        $this->sdk->setAccessToken($token);

        $this->assertEquals($token, $this->sdk->getToken());
    }

    public function testCanLogout(): void
    {
        $this->sdk->setAccessToken('test_token');
        $this->assertNotNull($this->sdk->getToken());

        $this->sdk->logout();
        $this->assertNull($this->sdk->getToken());
    }
}
