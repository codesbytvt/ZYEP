<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $packages = [
            [
                'name' => 'Starter Pack',
                'credits' => 10,
                'price' => 199.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Value Pack',
                'credits' => 30,
                'price' => 499.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro Pack',
                'credits' => 60,
                'price' => 899.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($packages as $pkg) {
            DB::table('mcredit_packages')->updateOrInsert(
                ['name' => $pkg['name']],
                $pkg
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mcredit_packages')->whereIn('name', [
            'Starter Pack',
            'Value Pack',
            'Pro Pack',
        ])->delete();
    }
};
