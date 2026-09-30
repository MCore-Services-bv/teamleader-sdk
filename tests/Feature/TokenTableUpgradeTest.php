<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use McoreServices\TeamleaderSDK\Services\TokenService;
use McoreServices\TeamleaderSDK\Tests\TestCase;
use McoreServices\TeamleaderSDK\Tokens\DatabaseTokenStore;

/**
 * v3.0 (B7): a v2.x token table upgrades in place with `php artisan migrate`,
 * and the SDK keeps working with the tokens it held.
 */
final class TokenTableUpgradeTest extends TestCase
{
    private const MIGRATIONS = __DIR__.'/../../database/migrations';

    protected function setUp(): void
    {
        parent::setUp();

        // Replace the v3.0 table with the one v2.x created on first use
        Schema::drop('teamleader_tokens');
        Schema::create('teamleader_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50)->default('Bearer');
            $table->integer('expires_in');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('expires_at');
            $table->index('updated_at');
        });
    }

    public function test_the_newest_row_becomes_the_default_connection(): void
    {
        $this->legacyRow('older-access', 'older-refresh', '2026-09-01 10:00:00');
        $this->legacyRow('newer-access', 'newer-refresh', '2026-09-30 10:00:00');

        $this->runMigration('2026_10_01_000001_upgrade_teamleader_tokens_table.php');

        $this->assertTrue(Schema::hasColumns('teamleader_tokens', ['connection', 'status', 'account_id', 'account_name', 'last_refreshed_at']));
        $this->assertSame(1, DB::table('teamleader_tokens')->count());

        $row = DB::table('teamleader_tokens')->first();
        $this->assertSame('default', $row->connection);
        $this->assertSame('connected', $row->status);
        $this->assertSame('newer-access', $row->access_token);
    }

    public function test_the_sdk_reads_the_upgraded_tokens_and_encrypts_them(): void
    {
        $this->legacyRow('legacy-access', 'legacy-refresh', now()->toDateTimeString(), expiresAt: now()->addHour()->toDateTimeString());

        $this->runMigration('2026_10_01_000001_upgrade_teamleader_tokens_table.php');

        $this->assertSame('legacy-access', (new TokenService(new DatabaseTokenStore))->getValidAccessToken());
        $this->assertSame('legacy-refresh', Crypt::decryptString(DB::table('teamleader_tokens')->value('refresh_token')));
    }

    public function test_the_create_migration_leaves_an_existing_table_alone(): void
    {
        $this->legacyRow('kept', 'kept', now()->toDateTimeString());

        $this->runMigration('2026_10_01_000000_create_teamleader_tokens_table.php');

        $this->assertFalse(Schema::hasColumn('teamleader_tokens', 'connection'));
        $this->assertSame(1, DB::table('teamleader_tokens')->count());
    }

    public function test_the_upgrade_is_safe_to_run_twice(): void
    {
        $this->legacyRow('access', 'refresh', now()->toDateTimeString());

        $this->runMigration('2026_10_01_000001_upgrade_teamleader_tokens_table.php');
        $this->runMigration('2026_10_01_000001_upgrade_teamleader_tokens_table.php');

        $this->assertSame(1, DB::table('teamleader_tokens')->count());
    }

    private function runMigration(string $file): void
    {
        (require self::MIGRATIONS.'/'.$file)->up();
    }

    private function legacyRow(string $access, string $refresh, string $updatedAt, ?string $expiresAt = null): void
    {
        DB::table('teamleader_tokens')->insert([
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => $expiresAt ?? $updatedAt,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
