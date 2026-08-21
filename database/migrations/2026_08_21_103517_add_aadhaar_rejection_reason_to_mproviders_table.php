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
            $table->text('aadhaar_rejection_reason')->nullable()->after('aadhaar_document_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mproviders', function (Blueprint $table) {
            $table->dropColumn('aadhaar_rejection_reason');
        });
    }
};
