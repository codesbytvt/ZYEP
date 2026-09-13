<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Retrieve public application settings.
     */
    public function getPublicSettings(Request $request)
    {
        return response()->json([
            'app_name' => Setting::get('app_name', 'ZYEP'),
            'demo_mode' => Setting::get('demo_mode', false),
            'customer_terms' => Setting::get('customer_terms', 'Please agree to our terms and conditions.'),
            'provider_terms' => Setting::get('provider_terms', 'Please agree to our provider terms.'),
            'terms_version' => Setting::get('terms_version', 1),
            'privacy_policy' => Setting::get('privacy_policy', 'Privacy policy coming soon.'),
        ]);
    }
}
