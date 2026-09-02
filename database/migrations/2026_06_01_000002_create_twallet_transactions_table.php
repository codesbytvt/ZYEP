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
        // Append-only ledger. Balance on mwallets is a cached total; this table is the
        // source of truth and is never updated or deleted, only inserted into (a refund
        // is a new row that references the original, not an edit of it).
        Schema::create('twallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('mwallets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('musers')->onDelete('cascade');

            $table->enum('type', ['topup', 'debit', 'refund', 'bonus', 'adjustment']);

            // Signed: positive for topup/refund/bonus/positive-adjustment,
            // negative for debit/negative-adjustment.
            $table->integer('credits');

            // Snapshot of the wallet balance immediately after this row was applied —
            // makes the ledger self-auditing without recomputing a running sum.
            $table->unsignedInteger('balance_after');

            // What this transaction is about, e.g. 'topup_payment', 'poster_generation',
            // 'admin_adjustment'. Deliberately a loose string + id pair (not a DB FK)
            // since the consuming feature (e.g. poster generation) may not exist yet
            // when this ledger is introduced, and may live behind its own table later.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Links a topup to the Razorpay-backed payment record that funded it.
            $table->foreignId('payment_id')->nullable()->constrained('tpayments')->onDelete('set null');

            // If a debit is later reversed (e.g. AI generation failed), the original
            // debit row is marked 'reversed' and a new 'refund' row is inserted —
            // the ledger is never mutated in place.
            $table->enum('status', ['completed', 'reversed'])->default('completed');

            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('musers')->onDelete('set null');

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twallet_transactions');
    }
};
