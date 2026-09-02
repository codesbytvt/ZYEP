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
        // One row per verification attempt (a provider may retry), not one row per
        // provider — mirrors how ActionLog/SearchLog record events rather than state.
        Schema::create('tkyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('mproviders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('musers')->onDelete('cascade');

            // Future-proofs for PAN/GST checks later without a schema change.
            $table->string('method')->default('aadhaar_otp');

            // Which KYC vendor handled this attempt — lets us switch or A/B vendors
            // without losing history of which one verified a given provider.
            $table->string('vendor');
            $table->string('vendor_reference_id')->nullable();

            $table->enum('status', ['initiated', 'otp_sent', 'verified', 'failed', 'expired'])
                ->default('initiated');

            // Deliberately NOT storing the Aadhaar number. Only the last 4 digits
            // (as most vendors return by default) and the name UIDAI returns, which
            // we cross-check against the provider's declared name.
            $table->string('masked_aadhaar', 20)->nullable();
            $table->string('verified_name')->nullable();

            // Vendor's full JSON response for audit/support, encrypted at rest via
            // the model cast — see KycVerification::$casts. Never includes a raw
            // Aadhaar number; only the vendor's own masked/summary payload.
            $table->text('raw_response')->nullable();

            $table->unsignedTinyInteger('attempt_count')->default(1);

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['provider_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tkyc_verifications');
    }
};
