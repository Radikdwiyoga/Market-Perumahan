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
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->boolean('enable_delivery')->default(true)->after('close_time');
            $table->boolean('enable_pickup')->default(true)->after('enable_delivery');
            $table->unsignedBigInteger('delivery_fee')->default(0)->after('enable_pickup');
            $table->unsignedBigInteger('min_order_amount')->default(0)->after('delivery_fee');
            $table->unsignedBigInteger('free_shipping_threshold')->nullable()->after('min_order_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn(['enable_delivery', 'enable_pickup', 'delivery_fee', 'min_order_amount', 'free_shipping_threshold']);
        });
    }
};
