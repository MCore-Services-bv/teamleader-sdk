<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Services\TokenService;
use Illuminate\Support\Facades\Cache;

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService();
        Cache::flush();
    }

    public function testCanStoreTokens(): void
    {
        $tokens = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
        ];

        $this->tokenService->storeTokens($tokens);

        $this->assertTrue($this->tokenService->hasValidTokens());
        $this->assertEquals('test_access_token', $this->tokenService->getValidAccessToken());
    }

    public function testReturnsNullWhenNoTokensExist(): void
    {
        $this->assertNull($this->tokenService->getValidAccessToken());
        $this->assertFalse($this->tokenService->hasValidTokens());
    }

    public function testCanClearTokens(): void
    {
        $tokens = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
        ];

        $this->tokenService->storeTokens($tokens);
        $this->assertTrue($this->tokenService->hasValidTokens());

        $this->tokenService->clearTokens();
        $this->assertFalse($this->tokenService->hasValidTokens());
    }

    public function testDetectsExpiredTokens(): void
    {
        $tokens = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => -100, // Already expired
        ];

        $this->tokenService->storeTokens($tokens);

        // Token exists but is expired
        $this->assertFalse($this->tokenService->hasValidTokens());
    }
}
