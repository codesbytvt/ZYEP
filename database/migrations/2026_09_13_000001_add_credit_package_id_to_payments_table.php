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
        Schema::table('tpayments', function (Blueprint $table) {
            // Presence of this column marks the payment as a credit top-up order
            // rather than a subscription order (which is tracked via `subscriptions`).
            $table->foreignId('credit_package_id')->nullable()->after('user_id')
                ->constrained('mcredit_packages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tpayments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_package_id');
        });
    }
};
