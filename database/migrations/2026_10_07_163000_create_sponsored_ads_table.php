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
        Schema::create('sponsored_ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type', 20)->default('image'); // 'image' or 'video'
            $table->string('media_path');
            $table->string('link_url')->nullable();
            $table->text('caption')->nullable();
            $table->string('status', 20)->default('active'); // 'active' or 'inactive'
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['status', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsored_ads');
    }
};
