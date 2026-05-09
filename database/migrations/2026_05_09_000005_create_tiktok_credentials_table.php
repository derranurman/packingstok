<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('app_key')->nullable();
            $table->text('app_secret')->nullable(); // encrypted
            $table->string('shop_cipher')->nullable();
            $table->text('access_token')->nullable(); // encrypted
            $table->text('refresh_token')->nullable(); // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->enum('mode', ['live', 'mock'])->default('mock');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_credentials');
    }
};
