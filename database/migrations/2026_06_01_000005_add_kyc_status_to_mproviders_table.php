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
        Schema::table('mproviders', function (Blueprint $table) {
            $table->enum('kyc_status', ['not_started', 'pending', 'verified', 'failed'])
                ->default('not_started')
                ->after('is_verified');
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mproviders', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_verified_at']);
        });
    }
};
