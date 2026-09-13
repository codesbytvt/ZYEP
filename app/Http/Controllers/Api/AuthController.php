<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Services\OTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP to the user's phone number
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:10|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->otpService->sendOTP($request->phone);

        return response()->json([
            'message' => 'OTP sent successfully.',
        ]);
    }

    /**
     * Verify OTP and log in/register the user
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($this->otpService->verifyOTP($request->phone, $request->otp)) {
            // Find or create user
            $user = User::firstOrCreate(
                ['phone' => $request->phone],
                ['name' => 'User ' . substr($request->phone, -4)]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired OTP',
        ], 401);
    }

    /**
     * Log out the user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $user->update($request->only('name', 'email'));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get OTP for demo mode
     */
    public function getDemoOtp(Request $request)
    {
        if (!Setting::get('demo_mode', false)) {
            return response()->json(['message' => 'Demo mode disabled'], 403);
        }

        $otp = $this->otpService->getOTP($request->phone);
        
        if ($otp) {
            return response()->json(['otp' => (string)$otp]);
        }

        return response()->json(['message' => 'No OTP found for this number'], 404);
    }
    /**
     * Mark terms as accepted for the user
     */
    public function acceptTerms(Request $request)
    {
        $user = $request->user();
        $user->update([
            'terms_accepted_at' => now(),
            'onboarding_stage' => 'profile'
        ]);

        return response()->json([
            'message' => 'Terms accepted successfully',
            'user' => $user
        ]);
    }

    /**
     * Update onboarding progress
     */
    public function updateOnboarding(Request $request)
    {
        $user = $request->user();
        $stage = $request->stage;

        if ($stage === 'profile') {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'onboarding_stage' => 'location'
            ]);
        }

        if ($stage === 'location') {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'area' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'area' => $request->area,
                'onboarding_stage' => 'completed',
                'onboarding_completed_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Onboarding updated',
            'user' => $user
        ]);
    }
}
