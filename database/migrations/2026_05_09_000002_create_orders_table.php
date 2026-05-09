<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tiktok_order_id')->unique();
            $table->string('tracking_number')->nullable()->index();
            $table->string('courier')->default('JNT');
            $table->string('buyer_name')->nullable();
            $table->enum('status', ['pending', 'ready_to_pack', 'packed', 'cancelled'])
                ->default('ready_to_pack');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->foreignId('packed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
