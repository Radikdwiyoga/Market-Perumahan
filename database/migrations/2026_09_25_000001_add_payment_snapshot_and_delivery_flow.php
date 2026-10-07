<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (DB::getDriverName() === 'pgsql') {
            Schema::table('seller_orders', function (Blueprint $table) {
                $table->timestamp('payment_due_at')->nullable()->after('pickup_code');
            });
            DB::statement('ALTER TABLE seller_orders DROP CONSTRAINT IF EXISTS seller_orders_shipping_status_check');
            DB::statement("ALTER TABLE seller_orders ADD CONSTRAINT seller_orders_shipping_status_check CHECK (shipping_status IN ('pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'))");

            DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check');
            DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'))");
        } else {
            Schema::table('seller_orders', function (Blueprint $table) {
                $table->timestamp('payment_due_at')->nullable()->after('pickup_code');
                $table->enum('shipping_status', ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'])->default('pending')->change();
            });

            Schema::table('shipments', function (Blueprint $table) {
                $table->enum('status', ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'])->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check');
            DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'processing', 'ready', 'completed'))");

            DB::statement('ALTER TABLE seller_orders DROP CONSTRAINT IF EXISTS seller_orders_shipping_status_check');
            DB::statement("ALTER TABLE seller_orders ADD CONSTRAINT seller_orders_shipping_status_check CHECK (shipping_status IN ('pending', 'processing', 'ready', 'completed'))");

            Schema::table('seller_orders', function (Blueprint $table) {
                $table->dropColumn('payment_due_at');
            });
        } else {
            Schema::table('shipments', function (Blueprint $table) {
                $table->enum('status', ['pending', 'processing', 'ready', 'completed'])->default('pending')->change();
            });

            Schema::table('seller_orders', function (Blueprint $table) {
                $table->enum('shipping_status', ['pending', 'processing', 'ready', 'completed'])->default('pending')->change();
                $table->dropColumn('payment_due_at');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['qris_image_snapshot', 'rejection_reason']);
        });
    }
};
