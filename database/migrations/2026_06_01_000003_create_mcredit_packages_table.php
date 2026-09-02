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
        // Purchasable credit bundles (e.g. "10 credits for ₹199"), mirroring how
        // SubscriptionPackage already models purchasable plans.
        Schema::create('mcredit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('credits');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcredit_packages');
    }
};
