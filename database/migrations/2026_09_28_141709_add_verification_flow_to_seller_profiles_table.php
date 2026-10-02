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
            $table->enum('verification_status', ['draft', 'pending', 'approved', 'rejected'])->default('approved')->after('status');
            $table->text('rejection_reason')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->timestamp('submitted_at')->nullable()->after('verified_at');

            $table->index(['verification_status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropIndex(['verification_status', 'submitted_at']);
            $table->dropColumn(['verification_status', 'rejection_reason', 'verified_by', 'verified_at', 'submitted_at']);
        });
    }
};
