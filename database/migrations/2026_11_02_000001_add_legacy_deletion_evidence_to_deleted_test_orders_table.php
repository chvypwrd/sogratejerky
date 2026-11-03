<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deleted_test_orders', function (Blueprint $table) {
            $table->json('local_payment_evidence')->nullable();
            $table->text('override_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deleted_test_orders', function (Blueprint $table) {
            $table->dropColumn(['local_payment_evidence', 'override_reason']);
        });
    }
};
