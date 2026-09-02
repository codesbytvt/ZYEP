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
        Schema::create('mwallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('musers')->onDelete('cascade');
            // Credits, not a cash value — keeps this outside RBI's prepaid-instrument (PPI)
            // rules, which would apply to a wallet that holds redeemable rupee balance.
            $table->unsignedInteger('balance_credits')->default(0);
            $table->unsignedInteger('lifetime_purchased_credits')->default(0);
            $table->unsignedInteger('lifetime_spent_credits')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mwallets');
    }
};
