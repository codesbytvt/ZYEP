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
            $table->string('aadhaar_number', 12)->nullable()->after('preferred_call_time');
            $table->string('aadhaar_verification_method')->nullable()->after('aadhaar_number');
            $table->string('aadhaar_verification_status')->default('unverified')->after('aadhaar_verification_method');
            $table->timestamp('aadhaar_verified_at')->nullable()->after('aadhaar_verification_status');
            $table->string('aadhaar_document_path')->nullable()->after('aadhaar_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mproviders', function (Blueprint $table) {
            $table->dropColumn([
                'aadhaar_number',
                'aadhaar_verification_method',
                'aadhaar_verification_status',
                'aadhaar_verified_at',
                'aadhaar_document_path',
            ]);
        });
    }
};
