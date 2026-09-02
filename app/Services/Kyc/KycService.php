<?php

namespace App\Services\Kyc;

use App\Models\KycVerification;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;

class KycService
{
    public function __construct(private KycProviderInterface $provider)
    {
    }

    /**
     * Start Aadhaar OTP verification for a provider. Aadhaar number is used only
     * to call the vendor and is never persisted — the vendor's own masked
     * response is what gets stored.
     */
    public function initiate(Provider $provider, string $aadhaarNumber): KycVerification
    {
        $result = $this->provider->initiate($aadhaarNumber);

        $verification = KycVerification::create([
            'provider_id' => $provider->id,
            'user_id' => $provider->user_id,
            'method' => 'aadhaar_otp',
            'vendor' => config('services.kyc.default_provider', 'setu'),
            'vendor_reference_id' => $result['reference_id'],
            'status' => $result['status'],
            'initiated_at' => now(),
            // A provider's OTP window; adjust to match the vendor's actual OTP TTL.
            'expires_at' => now()->addMinutes(10),
        ]);

        $provider->update(['kyc_status' => 'pending']);

        return $verification;
    }

    /**
     * Confirm the OTP for an in-flight verification and update provider status.
     */
    public function verify(KycVerification $verification, string $otp): KycVerification
    {
        $result = $this->provider->verify($verification->vendor_reference_id, $otp);

        return DB::transaction(function () use ($verification, $result) {
            $verification->update([
                'status' => $result['verified'] ? 'verified' : 'failed',
                'masked_aadhaar' => $result['masked_aadhaar'],
                'verified_name' => $result['verified_name'],
                'raw_response' => $result['raw_response'],
                'verified_at' => $result['verified'] ? now() : null,
                'attempt_count' => $verification->attempt_count + 1,
            ]);

            $verification->provider->update([
                'kyc_status' => $result['verified'] ? 'verified' : 'failed',
                'kyc_verified_at' => $result['verified'] ? now() : null,
            ]);

            return $verification->fresh();
        });
    }
}
