<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('shipping_enabled')->default(true);
            $table->boolean('local_delivery_enabled')->default(false);
            $table->boolean('pickup_enabled')->default(false);
            $table->string('shipping_basis')->default('subtotal');
            $table->timestamps();
        });

        Schema::create('shipping_rate_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_value');
            $table->unsignedInteger('max_value');
            $table->unsignedInteger('fee_cents');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['min_value', 'max_value']);
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('zip_code', 20)->unique();
            $table->unsignedInteger('fee_cents');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->text('contact_details')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('fee_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('checkout_token', 100)->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('fulfillment_fee_cents');
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents');
            $table->string('fulfillment_method');
            $table->string('order_status')->default('pending_payment');
            $table->string('payment_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->json('billing_address_snapshot')->nullable();
            $table->json('fulfillment_address_snapshot')->nullable();
            $table->json('pickup_location_snapshot')->nullable();
            $table->json('fulfillment_details_snapshot')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('order_status');
            $table->index('payment_status');
            $table->index('fulfillment_status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name');
            $table->string('sku', 100);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->unsignedInteger('weight_grams')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('pickup_locations');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('shipping_rate_rules');
        Schema::dropIfExists('fulfillment_settings');
    }
};
