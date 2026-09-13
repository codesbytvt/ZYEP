<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use MysteryInfo\YourBulkSms\DTO\SendSmsResponse;
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
            // Let the client fall back to config('yourbulksms.route') rather than
            // forcing the dedicated OTP route -- not every account has that route
            // provisioned/funded, and forcing it here fails sends even when a
            // perfectly good route (e.g. Transactional) is available and paid for.
            $response = $this->smsClient->send(
                $phone,
                "Your ZYEP verification code is {$otp}. Valid for 10 minutes.",
            );

            $delivered = $this->wasAccepted($response);

            if (!$delivered) {
                Log::error("YourBulkSms OTP send failed for {$phone}: {$response->message}");
            }

            return $delivered;
        } catch (\Throwable $e) {
            Log::error("YourBulkSms OTP send error for {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * The package's ResponseCode table only recognizes the older plain-text
     * API's numeric codes (001-023) and treats anything else -- including this
     * gateway's own JSON success code "000" -- as a failure, even though the
     * submission was accepted. Check the raw JSON status directly as well.
     */
    private function wasAccepted(SendSmsResponse $response): bool
    {
        if ($response->success) {
            return true;
        }

        $decoded = is_array($response->data) ? $response->data : json_decode((string) $response->raw, true);

        return isset($decoded['Status']) && strcasecmp($decoded['Status'], 'Success') === 0;
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
