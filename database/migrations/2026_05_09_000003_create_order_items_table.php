<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tiktok_sku')->nullable();
            $table->string('tiktok_product_name')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();

            $table->index('tiktok_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
