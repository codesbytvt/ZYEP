<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use MysteryInfo\YourBulkSms\Support\Route;
use MysteryInfo\YourBulkSms\YourBulkSmsClient;

class OTPService
{
    public function __construct(private YourBulkSmsClient $smsClient)
    {
    }

    /**
     * Send OTP to a phone number via the YourBulkSms gateway.
     *
     * Falls back to logging the OTP (instead of sending a real SMS) when no
     * YOURBULKSMS_AUTHKEY is configured, so local/demo environments keep
     * working exactly like before without needing gateway credentials.
     */
    public function sendOTP($phone)
    {
        $otp = rand(100000, 999999);

        // Store in cache for 10 minutes
        Cache::put('otp_' . $phone, $otp, now()->addMinutes(10));

        if (!$this->gatewayConfigured()) {
            Log::info("Mock OTP for {$phone}: {$otp}");
            return true;
        }

        try {
            $response = $this->smsClient->send(
                $phone,
                "Your ZYEP verification code is {$otp}. Valid for 10 minutes.",
                ['route' => Route::OTP],
            );

            if (!$response->success) {
                Log::error("YourBulkSms OTP send failed for {$phone}: {$response->message}");
            }

            return $response->success;
        } catch (\Throwable $e) {
            Log::error("YourBulkSms OTP send error for {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify OTP for a phone number
     */
    public function verifyOTP($phone, $otp)
    {
        $cachedOtp = Cache::get('otp_' . $phone);

        if ($cachedOtp && $cachedOtp == $otp) {
            Cache::forget('otp_' . $phone);
            return true;
        }

        return false;
    }

    /**
     * Get OTP for a phone number (for Demo/Development)
     */
    public function getOTP($phone)
    {
        return Cache::get('otp_' . $phone);
    }

    /**
     * The package ships a non-empty placeholder authkey by default (its Config
     * throws if authkey is truly empty), so a placeholder is the signal that
     * no real gateway credentials have been set.
     */
    private function gatewayConfigured(): bool
    {
        $authkey = config('yourbulksms.authkey');

        return !empty($authkey) && $authkey !== 'YOUR_AUTH_KEY';
    }
}
