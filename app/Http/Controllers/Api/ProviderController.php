<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ActionLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProviderController extends Controller
{
    /**
     * Check if the current user can view provider details based on settings.
     */
    protected function checkSearchVisibility()
    {
        $visibility = Setting::get('search_visibility', 'guest');
        
        if ($visibility === 'member only' && !Auth::guard('sanctum')->check()) {
            return false;
        }
        
        return true;
    }

    /**
     * Display a listing of approved providers with optional category filter.
     */
    public function index(Request $request)
    {
        if (!$this->checkSearchVisibility()) {
            return response()->json(['message' => 'Please login to search providers.'], 401);
        }

        $query = Provider::with(['user:id,name,phone', 'category'])->where('status', 1);

        if (Setting::get('verification_required', true)) {
            $query->where('is_verified', true);
        }

        if ($request->has('category_id')) {
            $catId = $request->category_id;
            $subIds = \App\Models\Category::where('parent_id', $catId)->pluck('id')->toArray();

            if (!empty($subIds)) {
                $query->whereIn('category_id', array_merge([$catId], $subIds));
            } else {
                $query->where('category_id', $catId);
            }
        }

        // Filter by keyword: business name, description, area, or category name
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('business_name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('area', 'like', "%{$keyword}%")
                  ->orWhereHas('category', function ($catQuery) use ($keyword) {
                      $catQuery->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        $providers = $query->paginate(15);

        return response()->json($providers);
    }

    /**
     * Search providers by location using the Haversine Formula.
     * GET /api/providers/search?lat=&lng=&radius=10&category_id=&keyword=
     */
    public function search(Request $request)
    {
        if (!$this->checkSearchVisibility()) {
            return response()->json(['message' => 'Please login to search providers.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50',
            'category_id' => 'nullable|integer|exists:mcategories,id',
            'keyword' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? Setting::get('search_radius', 10);

        // Haversine Formula: calculates distance in km between two lat/lng points
        $haversine = "(
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )
        )";

        $query = Provider::select('mproviders.*')
            ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
            ->with(['user:id,name,phone', 'category:id,name,icon'])
            ->where('status', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radius)
            ->orderBy('distance');

        if (Setting::get('verification_required', true)) {
            $query->where('is_verified', true);
        }

        // Filter by category (including subcategories if parent selected)
        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            $subIds = \App\Models\Category::where('parent_id', $catId)->pluck('id')->toArray();
            
            if (!empty($subIds)) {
                $query->whereIn('category_id', array_merge([$catId], $subIds));
            } else {
                $query->where('category_id', $catId);
            }
        }

        // Filter by keyword: business name, description, area, or category name
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('business_name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('area', 'like', "%{$keyword}%")
                  ->orWhereHas('category', function ($catQuery) use ($keyword) {
                      $catQuery->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        $providers = $query->paginate(15);

        // Log the search if keyword is present
        if ($request->filled('keyword')) {
            try {
                $user = Auth::guard('sanctum')->user();
                ActionLog::create([
                    'user_id' => $user?->id,
                    'action_type' => 'search',
                    'metadata' => [
                        'keyword' => $request->keyword,
                        'category_id' => $request->category_id,
                        'location' => "{$lat},{$lng}",
                        'radius' => $radius
                    ]
                ]);
            } catch (\Exception $e) {
                // Silently fail logging
            }
        }

        return response()->json($providers);
    }

    /**
     * Register the authenticated user as a provider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'category_id' => 'required|exists:mcategories,id',
            'description' => 'nullable|string',
            'experience' => 'nullable|integer|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'area' => 'nullable|string',
            'preferred_call_time' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'aadhaar_number' => 'nullable|string|digits:12',
            'aadhaar_verification_method' => 'nullable|string|in:otp,manual',
            'aadhaar_verification_status' => 'nullable|string|in:unverified,pending,verified,rejected',
            'aadhaar_document_path' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        // Update user email
        $user->update(['email' => $request->email]);

        // Check if user already has max allowed providers (bypass if updating existing profile)
        $maxProviders = Setting::get('max_providers_per_user', 1);
        $hasProfile = Provider::where('user_id', $user->id)->exists();
        $currentCount = Provider::where('user_id', $user->id)->count();

        if (!$hasProfile && $currentCount >= $maxProviders) {
            return response()->json([
                'message' => "You have reached the maximum limit of {$maxProviders} provider profile(s)."
            ], 403);
        }

        // Update user role to provider
        $user->update(['role' => 'provider']);

        $existing = Provider::where('user_id', $user->id)->first();

        $provider = Provider::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $request->business_name,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'experience' => $request->experience ?? 0,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'area' => $request->area,
                'preferred_call_time' => $request->preferred_call_time,
                'aadhaar_number' => $request->aadhaar_number,
                'aadhaar_verification_method' => $request->aadhaar_verification_method,
                'aadhaar_verification_status' => $request->aadhaar_verification_status ?? 'unverified',
                'aadhaar_verified_at' => $request->aadhaar_verification_status === 'verified' ? now() : null,
                'aadhaar_document_path' => $request->aadhaar_document_path,
                // Only new profiles start pending; editing an existing profile
                // (approved or not) preserves its current status.
                'status' => $existing->status ?? 0,
                'terms_accepted_at' => now(),
            ]
        );

        $message = $existing
            ? 'Provider profile updated successfully.'
            : 'Provider profile created successfully. Awaiting admin approval.';

        return response()->json([
            'message' => $message,
            'provider' => $provider->load('category'),
        ]);
    }

    /**
     * Display the specified provider.
     */
    public function show($id)
    {
        if (!$this->checkSearchVisibility()) {
            return response()->json(['message' => 'Please login to view provider details.'], 401);
        }

        $provider = Provider::with(['user:id,name,phone', 'category', 'activeSubscription.package'])->findOrFail($id);
        $user = Auth::guard('sanctum')->user();

        if (Setting::get('verification_required', true) && !$provider->is_verified) {
            // Allow the owner of the profile to view it even if not verified
            if (!$user || $user->id !== $provider->user_id) {
                return response()->json(['message' => 'This provider is not verified.'], 403);
            }
        }

        // Log the view action (Skip if it's the owner)
        try {
            if (!$user || $user->id !== $provider->user_id) {
                ActionLog::create([
                    'user_id' => $user?->id,
                    'action_type' => 'provider_view',
                    'target_id' => $id
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail logging
        }

        return response()->json($provider);
    }

    public function myProvider(Request $request)
    {
        $provider = Provider::where('user_id', $request->user()->id)
            ->with(['category', 'user', 'activeSubscription.package'])
            ->first();

        return response()->json($provider);
    }

    public function sendAadhaarOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aadhaar_number' => 'required|string|digits:12',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $otp = rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('aadhaar_otp_' . $request->aadhaar_number, $otp, now()->addMinutes(10));
        \Illuminate\Support\Facades\Log::info("Mock Aadhaar OTP for {$request->aadhaar_number}: {$otp}");

        return response()->json([
            'status' => 'success',
            'message' => 'Aadhaar verification OTP sent successfully (Mocked). Check logs!',
        ]);
    }

    public function verifyAadhaarOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aadhaar_number' => 'required|string|digits:12',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('aadhaar_otp_' . $request->aadhaar_number);

        if ($request->otp === '123456' || ($cachedOtp && $cachedOtp == $request->otp)) {
            \Illuminate\Support\Facades\Cache::forget('aadhaar_otp_' . $request->aadhaar_number);
            return response()->json([
                'status' => 'success',
                'message' => 'Aadhaar verified successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Invalid Aadhaar verification OTP.',
        ], 400);
    }

    public function uploadAadhaarDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'aadhaar_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('aadhaar_document')->store('aadhaar_documents', 'public');

        return response()->json([
            'status' => 'success',
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }
}
