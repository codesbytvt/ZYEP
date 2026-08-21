<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'mproviders';

    // NOTE: rating/status/is_verified are privileged fields, kept mass-assignable
    // only because the Filament admin resource needs to set them directly.
    // Never fill these from raw request input in an API controller — build an
    // explicit array instead (see ProviderController::store for the pattern).
    protected $fillable = [
        'user_id',
        'business_name',
        'category_id',
        'description',
        'experience',
        'latitude',
        'longitude',
        'area',
        'preferred_call_time',
        'aadhaar_number',
        'aadhaar_verification_method',
        'aadhaar_verification_status',
        'aadhaar_verified_at',
        'aadhaar_document_path',
        'aadhaar_rejection_reason',
        'rating',
        'status',
        'is_verified',
        'terms_accepted_at',
    ];

    protected static function booted()
    {
        static::updating(function ($provider) {
            if ($provider->isDirty('aadhaar_verification_status')) {
                $newStatus = $provider->aadhaar_verification_status;
                
                if ($newStatus === 'verified') {
                    $provider->is_verified = true;
                    $provider->aadhaar_verified_at = now();
                } else {
                    $provider->is_verified = false;
                    $provider->aadhaar_verified_at = null;
                }
                
                if ($newStatus === 'rejected') {
                    try {
                        \Illuminate\Support\Facades\Mail::to($provider->user->email)
                            ->send(new \App\Mail\AadhaarVerificationRejected($provider));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send Aadhaar rejection email: " . $e->getMessage());
                    }
                } else {
                    $provider->aadhaar_rejection_reason = null;
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }
}
