<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Tokens;

use Carbon\CarbonImmutable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Tokens\DatabaseTokenStore;
use McoreServices\TeamleaderSDK\Tokens\StoredTokens;
use McoreServices\TeamleaderSDK\Tokens\TokenStorageException;

/**
 * v3.0 (B5, B7): one row per connection, tokens encrypted at rest, and v2.x
 * plain-text rows encrypted on first read.
 */
final class DatabaseTokenStoreTest extends TestCase
{
    private DatabaseTokenStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new DatabaseTokenStore;
    }

    public function test_tokens_round_trip(): void
    {
        $this->store->put('default', $this->tokens('access-1', 'refresh-1', accountName: 'MCore Services'));

        $read = $this->store->get('default');

        $this->assertSame('access-1', $read->accessToken);
        $this->assertSame('refresh-1', $read->refreshToken);
        $this->assertSame('MCore Services', $read->accountName);
        $this->assertSame(StoredTokens::CONNECTED, $read->status);
        $this->assertEqualsWithDelta(3600, $read->secondsUntilExpiry(), 5);
    }

    public function test_tokens_are_encrypted_in_the_table(): void
    {
        $this->store->put('default', $this->tokens('access-plain', 'refresh-plain'));

        $row = DB::table('teamleader_tokens')->where('connection', 'default')->first();

        $this->assertStringNotContainsString('access-plain', $row->access_token);
        $this->assertStringNotContainsString('refresh-plain', $row->refresh_token);
        $this->assertSame('access-plain', Crypt::decryptString($row->access_token));
        $this->assertSame('refresh-plain', Crypt::decryptString($row->refresh_token));
    }

    public function test_connections_are_kept_apart(): void
    {
        $this->store->put('antwerp', $this->tokens('access-antwerp', 'refresh-antwerp'));
        $this->store->put('ghent', $this->tokens('access-ghent', 'refresh-ghent'));

        $this->assertSame('access-antwerp', $this->store->get('antwerp')->accessToken);
        $this->assertSame('access-ghent', $this->store->get('ghent')->accessToken);
        $this->assertNull($this->store->get('default'));
        $this->assertSame(['antwerp', 'ghent'], $this->store->connections());

        $this->store->forget('antwerp');

        $this->assertNull($this->store->get('antwerp'));
        $this->assertSame('access-ghent', $this->store->get('ghent')->accessToken);
    }

    public function test_put_replaces_the_row_for_a_connection(): void
    {
        $this->store->put('default', $this->tokens('first', 'refresh'));
        $this->store->put('default', $this->tokens('second', 'refresh'));

        $this->assertSame(1, DB::table('teamleader_tokens')->count());
        $this->assertSame('second', $this->store->get('default')->accessToken);
    }

    public function test_a_plain_text_row_from_2x_is_read_and_encrypted(): void
    {
        DB::table('teamleader_tokens')->insert([
            'connection' => 'default',
            'access_token' => 'legacy-access',
            'refresh_token' => 'legacy-refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => CarbonImmutable::now()->addHour()->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $read = $this->store->get('default');

        $this->assertSame('legacy-access', $read->accessToken);
        $this->assertSame('legacy-refresh', $read->refreshToken);

        $row = DB::table('teamleader_tokens')->first();
        $this->assertSame('legacy-access', Crypt::decryptString($row->access_token));
        $this->assertSame('legacy-refresh', Crypt::decryptString($row->refresh_token));
    }

    public function test_tokens_encrypted_with_another_app_key_fail_clearly(): void
    {
        $otherKey = new Encrypter(random_bytes(32), 'aes-256-cbc');

        DB::table('teamleader_tokens')->insert([
            'connection' => 'default',
            'access_token' => $otherKey->encryptString('access'),
            'refresh_token' => $otherKey->encryptString('refresh'),
            'expires_in' => 3600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(TokenStorageException::class);
        $this->expectExceptionMessage("connection 'default' cannot be decrypted");

        $this->store->get('default');
    }

    public function test_a_missing_table_names_the_fix(): void
    {
        Schema::drop('teamleader_tokens');

        $this->expectException(TokenStorageException::class);
        $this->expectExceptionMessage('php artisan migrate');

        $this->store->get('default');
    }

    private function tokens(string $access, ?string $refresh, ?string $accountName = null): StoredTokens
    {
        return new StoredTokens(
            accessToken: $access,
            refreshToken: $refresh,
            expiresAt: CarbonImmutable::now()->addHour(),
            accountName: $accountName,
        );
    }
}
