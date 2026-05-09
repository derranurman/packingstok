<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->string('source', 30)->default('upload'); // 'upload' | 'watch_folder'
            $table->unsignedInteger('rows_read')->default(0);
            $table->unsignedInteger('orders_created')->default(0);
            $table->unsignedInteger('orders_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('warnings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_imports');
    }
};
