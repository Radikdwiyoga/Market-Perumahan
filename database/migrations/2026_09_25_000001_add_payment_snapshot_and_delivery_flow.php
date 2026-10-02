<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('qris_image_snapshot')->nullable()->after('proof_image');
            $table->string('rejection_reason')->nullable()->after('qris_image_snapshot');
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->timestamp('payment_due_at')->nullable()->after('pickup_code');
            $table->enum('shipping_status', ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'])->default('pending')->change();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'ready', 'completed'])->default('pending')->change();
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->enum('shipping_status', ['pending', 'processing', 'ready', 'completed'])->default('pending')->change();
            $table->dropColumn('payment_due_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['qris_image_snapshot', 'rejection_reason']);
        });
    }
};
