<?php

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Tests\TestCase;

/**
 * Regression tests for the v2.1.1 token cache fix.
 *
 * Before v2.1.1 the SDK cached a live Carbon instance under the
 * `_expires_at` key. When the cache store could not rehydrate that object it
 * returned __PHP_Incomplete_Class, and Carbon::parse() threw a TypeError.
 *
 * File: tests/Unit/Services/TokenServiceCacheIntegrityTest.php
 */
class TokenServiceCacheIntegrityTest extends TestCase
{
    private const ACCESS_TOKEN_KEY = 'teamleader_access_token';

    private const EXPIRES_AT_KEY = 'teamleader_access_token_expires_at';

    private const REFRESH_TOKEN_KEY = 'teamleader_refresh_token';

    private TokenService $tokenService;

    public function test_expires_at_is_cached_as_a_scalar(): void
    {
        $this->tokenService->storeTokens([
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
        ]);

        $cached = Cache::get(self::EXPIRES_AT_KEY);

        $this->assertIsString($cached, 'expires_at must be cached as a string, never as an object');
    }

    public function test_unreadable_cached_expiry_does_not_throw_and_self_heals(): void
    {
        $this->tokenService->storeTokens([
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
        ]);

        // Reproduce exactly what an older SDK version left behind: a value the
        // cache store could not turn back into a real object.
        Cache::put(self::EXPIRES_AT_KEY, $this->incompleteObject(), 3600);

        // Previously a TypeError; must now fall back to the database.
        $this->assertTrue($this->tokenService->hasValidTokens());

        // The poisoned entries are purged so the next read is clean.
        $this->assertNull(Cache::get(self::EXPIRES_AT_KEY));
        $this->assertNull(Cache::get(self::ACCESS_TOKEN_KEY));
        $this->assertNull(Cache::get(self::REFRESH_TOKEN_KEY));

        // And the cache is repopulated from the database with a scalar.
        $this->assertEquals('test_access_token', $this->tokenService->getValidAccessToken());
        $this->assertIsString(Cache::get(self::EXPIRES_AT_KEY));
    }

    /**
     * Build a __PHP_Incomplete_Class the same way PHP does when it cannot
     * resolve a serialized class.
     */
    private function incompleteObject(): object
    {
        return unserialize('O:12:"MissingClass":0:{}');
    }

    public function test_token_info_does_not_throw_on_unreadable_cached_expiry(): void
    {
        $this->tokenService->storeTokens([
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
        ]);

        Cache::put(self::EXPIRES_AT_KEY, $this->incompleteObject(), 3600);

        $info = $this->tokenService->getTokenInfo();

        $this->assertTrue($info['has_access_token']);
        $this->assertNotNull($info['expires_at']);
    }

    public function test_missing_cached_expiry_does_not_throw(): void
    {
        Cache::put(self::ACCESS_TOKEN_KEY, 'orphan_access_token', 3600);
        Cache::put(self::REFRESH_TOKEN_KEY, 'orphan_refresh_token', 3600);

        // Unchanged behaviour: an unknown expiry is not treated as expired by
        // hasValidTokens(), but shouldRefreshToken() will force a refresh.
        $this->assertTrue($this->tokenService->hasValidTokens());
        $this->assertTrue($this->tokenService->getTokenInfo()['needs_refresh']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService;
        Cache::flush();
    }
}
