<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teamleader_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 500);
            $table->string('refresh_token', 500);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teamleader_tokens');
    }
};
