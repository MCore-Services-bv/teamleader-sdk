<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Credentials of connections added with `php artisan teamleader:connections:add`,
 * so a new Teamleader account needs no deploy. Client ID and secret are
 * encrypted with APP_KEY. Connections in config/teamleader.php do not use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teamleader_connections')) {
            return;
        }

        Schema::create('teamleader_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('client_id');
            $table->text('client_secret');
            $table->string('redirect_uri')->nullable();
            $table->string('expected_account_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teamleader_connections');
    }
};
