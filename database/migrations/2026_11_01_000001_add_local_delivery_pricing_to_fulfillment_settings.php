<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_settings', function (Blueprint $table) {
            $table->boolean('local_delivery_threshold_enabled')->default(false);
            $table->unsignedInteger('local_delivery_threshold_cents')->default(0);
            $table->unsignedInteger('local_delivery_flat_fee_cents')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'local_delivery_threshold_enabled',
                'local_delivery_threshold_cents',
                'local_delivery_flat_fee_cents',
            ]);
        });
    }
};
