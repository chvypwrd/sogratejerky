<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_test_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_order_id');
            $table->string('order_number', 100);
            $table->unsignedBigInteger('original_total_cents')->default(0);
            $table->timestamp('original_placed_at')->nullable();
            $table->string('verification_basis', 255);
            $table->json('record_counts');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deleted_by_name', 255);
            $table->timestamp('deleted_at')->useCurrent();
            $table->timestamps();

            $table->index('order_number');
        });

        Schema::create('deleted_test_order_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deleted_test_order_id')->constrained('deleted_test_orders')->cascadeOnDelete();
            $table->string('payment_intent_id', 255)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_test_order_payment_intents');
        Schema::dropIfExists('deleted_test_orders');
    }
};
