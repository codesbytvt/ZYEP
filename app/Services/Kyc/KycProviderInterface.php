<?php

namespace App\Services\Kyc;

interface KycProviderInterface
{
    /**
     * Kick off Aadhaar OTP verification: the vendor triggers UIDAI to send an
     * OTP to the mobile number linked to the given Aadhaar number.
     *
     * @return array{reference_id: string, status: string}
     */
    public function initiate(string $aadhaarNumber): array;

    /**
     * Confirm the OTP the provider received and pull back the verification
     * result. Never returns a full Aadhaar number — only what the vendor's
     * masked/summary response contains.
     *
     * @return array{
     *   verified: bool,
     *   masked_aadhaar: ?string,
     *   verified_name: ?string,
     *   raw_response: array,
     * }
     */
    public function verify(string $referenceId, string $otp): array;
}
