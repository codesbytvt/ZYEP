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
            $table->string('preferred_call_time')->nullable()->after('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mproviders', function (Blueprint $table) {
            $table->dropColumn('preferred_call_time');
        });
    }
};
