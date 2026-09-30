<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Upgrades a v2.x token table in place.
 *
 * v2.x kept a single account's tokens, created the table on first use and
 * normally held one row. That row becomes the `default` connection. If there
 * are several, the most recently updated one is kept — it is the one v2.x
 * read — and the others are deleted.
 *
 * The tokens themselves are still plain text after this migration. The SDK
 * encrypts each row the first time it reads it.
 *
 * Does nothing on a table created by the v3.0 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teamleader_tokens') || Schema::hasColumn('teamleader_tokens', 'connection')) {
            return;
        }

        $newest = DB::table('teamleader_tokens')->orderByDesc('updated_at')->orderByDesc('id')->value('id');

        if ($newest !== null) {
            DB::table('teamleader_tokens')->where('id', '!=', $newest)->delete();
        }

        Schema::table('teamleader_tokens', function (Blueprint $table) {
            $table->string('connection', 100)->default('default');
            $table->string('status', 32)->default('connected');
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
        });

        Schema::table('teamleader_tokens', function (Blueprint $table) {
            $table->unique('connection');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teamleader_tokens') || ! Schema::hasColumn('teamleader_tokens', 'connection')) {
            return;
        }

        Schema::table('teamleader_tokens', function (Blueprint $table) {
            $table->dropUnique(['connection']);
        });

        Schema::table('teamleader_tokens', function (Blueprint $table) {
            $table->dropColumn(['connection', 'status', 'account_id', 'account_name', 'last_refreshed_at']);
        });
    }
};
