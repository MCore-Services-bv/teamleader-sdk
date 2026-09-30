<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use ReflectionProperty;

/**
 * The token cache: one encrypted entry per connection.
 *
 * Until v3.0 the cache held the access and refresh token in plain text under
 * three fixed keys. Before v2.1.1 one of them could also hold a Carbon object,
 * which a cache store could return as __PHP_Incomplete_Class. Both are covered:
 * whatever unreadable value is found is purged and the store read instead.
 */
final class TokenServiceCacheIntegrityTest extends TestCase
{
    private const TOKENS = ['access_token' => 'test_access_token', 'refresh_token' => 'test_refresh_token', 'expires_in' => 3600];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_the_cache_holds_one_encrypted_entry_and_no_plain_token(): void
    {
        (new TokenService)->storeTokens(self::TOKENS);

        $cached = Cache::get('teamleader:default:tokens');

        $this->assertIsString($cached);
        $this->assertStringNotContainsString('test_access_token', $cached);
        $this->assertStringNotContainsString('test_refresh_token', $cached);
        $this->assertSame('test_access_token', json_decode(Crypt::decryptString($cached), true)['access_token']);
    }

    public function test_connections_have_their_own_cache_entry(): void
    {
        (new TokenService(null, 'antwerp'))->storeTokens(['access_token' => 'antwerp-access', 'expires_in' => 3600]);
        (new TokenService(null, 'ghent'))->storeTokens(['access_token' => 'ghent-access', 'expires_in' => 3600]);

        $this->assertSame('antwerp-access', (new TokenService(null, 'antwerp'))->getValidAccessToken());
        $this->assertSame('ghent-access', (new TokenService(null, 'ghent'))->getValidAccessToken());
        $this->assertNull((new TokenService(null, 'default'))->getValidAccessToken());
    }

    public function test_an_unreadable_cache_entry_is_purged_and_the_store_used(): void
    {
        $service = new TokenService;
        $service->storeTokens(self::TOKENS);

        Cache::put('teamleader:default:tokens', unserialize('O:12:"MissingClass":0:{}'), 3600);

        $this->assertSame('test_access_token', $service->getValidAccessToken());
        $this->assertIsString(Cache::get('teamleader:default:tokens'), 'Re-cached from the store');
    }

    public function test_a_cache_entry_encrypted_with_another_key_is_purged(): void
    {
        $service = new TokenService;
        $service->storeTokens(self::TOKENS);

        Cache::put('teamleader:default:tokens', 'eyJpdiI6Im5vdCJ9', 3600);

        $this->assertTrue($service->hasValidTokens());

        // Replaced by a readable entry from the store
        $cached = json_decode(Crypt::decryptString(Cache::get('teamleader:default:tokens')), true);
        $this->assertSame('test_access_token', $cached['access_token']);
    }

    public function test_the_plain_text_cache_keys_of_2x_are_removed(): void
    {
        (new ReflectionProperty(TokenService::class, 'legacyCacheCleared'))->setValue(null, false);

        Cache::put('teamleader_access_token', 'legacy-access', 3600);
        Cache::put('teamleader_refresh_token', 'legacy-refresh', 3600);

        new TokenService;

        $this->assertNull(Cache::get('teamleader_access_token'));
        $this->assertNull(Cache::get('teamleader_refresh_token'));
    }

    public function test_token_info_reports_the_connection_and_source(): void
    {
        $service = new TokenService(null, 'antwerp');
        $service->storeTokens(self::TOKENS);

        $info = $service->getTokenInfo();

        $this->assertSame('antwerp', $info['connection']);
        $this->assertSame('cache', $info['token_source']);
        $this->assertTrue($info['database_has_tokens']);
        $this->assertFalse($info['needs_refresh']);

        Cache::flush();

        $this->assertSame('database', $service->getTokenInfo()['token_source']);
    }
}
