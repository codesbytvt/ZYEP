<?php

namespace App\Services\Kyc;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scaffold only. The exact endpoint paths, headers, and payload shape below are
 * placeholders — Setu's official API docs (available after signup) must be
 * consulted before this talks to a real sandbox/production key. Nothing here
 * has been verified against a live Setu account.
 */
class SetuKycProvider implements KycProviderInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.setu.base_url', 'https://dg-sandbox.setu.co');
        $this->clientId = config('services.setu.client_id');
        $this->clientSecret = config('services.setu.client_secret');
    }

    public function initiate(string $aadhaarNumber): array
    {
        // TODO: confirm exact path/payload against Setu's eKYC API reference.
        $response = Http::withHeaders($this->authHeaders())
            ->post("{$this->baseUrl}/api/kyc/aadhaar-otp/initiate", [
                'aadhaarNumber' => $aadhaarNumber,
            ]);

        if ($response->failed()) {
            Log::error('Setu KYC initiate failed', ['body' => $response->body()]);
            throw new \RuntimeException('Unable to start Aadhaar verification right now.');
        }

        $data = $response->json();

        return [
            'reference_id' => $data['id'] ?? $data['reference_id'],
            'status' => 'otp_sent',
        ];
    }

    public function verify(string $referenceId, string $otp): array
    {
        // TODO: confirm exact path/payload against Setu's eKYC API reference.
        $response = Http::withHeaders($this->authHeaders())
            ->post("{$this->baseUrl}/api/kyc/aadhaar-otp/{$referenceId}/verify", [
                'otp' => $otp,
            ]);

        if ($response->failed()) {
            Log::error('Setu KYC verify failed', ['body' => $response->body()]);

            return [
                'verified' => false,
                'masked_aadhaar' => null,
                'verified_name' => null,
                'raw_response' => $response->json() ?? [],
            ];
        }

        $data = $response->json();

        return [
            'verified' => ($data['status'] ?? null) === 'verified',
            'masked_aadhaar' => $data['aadhaarNumber'] ?? null, // vendor returns this pre-masked
            'verified_name' => $data['name'] ?? null,
            'raw_response' => $data,
        ];
    }

    private function authHeaders(): array
    {
        // TODO: confirm Setu's actual auth scheme (bearer token vs signed headers)
        // from their API docs — this is a placeholder shape.
        return [
            'client-id' => $this->clientId,
            'client-secret' => $this->clientSecret,
        ];
    }
}
