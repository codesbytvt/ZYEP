<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Matches the admin user seeded in 2026_05_20_130000_seed_admin_user_and_subscription_packages.
        // Change this password after first login.
        DB::table('musers')
            ->where('phone', '9207908701')
            ->update(['password' => Hash::make('zyep-admin')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('musers')
            ->where('phone', '9207908701')
            ->update(['password' => null]);
    }
};
