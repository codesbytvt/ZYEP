<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingsController::class, 'getPublicSettings']);

Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::get('/demo-otp', [AuthController::class, 'getDemoOtp']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::put('/user', [AuthController::class, 'updateProfile']);
        Route::post('/user/accept-terms', [AuthController::class, 'acceptTerms']);
        Route::post('/user/update-onboarding', [AuthController::class, 'updateOnboarding']);
    });
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/packages', [PackageController::class, 'index']);
Route::get('/providers', [ProviderController::class, 'index']);
Route::get('/providers/search', [ProviderController::class, 'search']);
Route::get('/providers/{id}', [ProviderController::class, 'show']);
Route::post('/analytics/log', [AnalyticsController::class, 'logAction'])->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/providers', [ProviderController::class, 'store']); // Register as provider
    Route::get('/user/provider', [ProviderController::class, 'myProvider']);
    
    // Aadhaar Verification
    Route::post('/providers/aadhaar/send-otp', [ProviderController::class, 'sendAadhaarOtp']);
    Route::post('/providers/aadhaar/verify-otp', [ProviderController::class, 'verifyAadhaarOtp']);
    Route::post('/providers/aadhaar/upload-document', [ProviderController::class, 'uploadAadhaarDocument']);

    // Payments
    Route::post('/payments/order', [PaymentController::class, 'createOrder']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
});
