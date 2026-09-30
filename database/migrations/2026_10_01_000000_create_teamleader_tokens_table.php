<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The token table for new installations.
 *
 * An installation upgraded from v2.x already has the table (the SDK created it
 * on first use). This migration then does nothing, and the next one upgrades
 * that table in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teamleader_tokens')) {
            return;
        }

        Schema::create('teamleader_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('connection', 100)->unique();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type', 50)->default('Bearer');
            $table->integer('expires_in')->default(3600);
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('status', 32)->default('connected');
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teamleader_tokens');
    }
};
