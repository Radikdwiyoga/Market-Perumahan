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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['seller_delivery', 'store_pickup']);
            $table->string('address');
            $table->string('recipient_name');
            $table->string('recipient_phone', 30);
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->enum('status', ['pending', 'processing', 'ready', 'completed'])->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
