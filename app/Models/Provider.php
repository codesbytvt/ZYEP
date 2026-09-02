<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'mproviders';

    protected $fillable = [
        'user_id',
        'business_name',
        'category_id',
        'description',
        'experience',
        'latitude',
        'longitude',
        'area',
        'rating',
        'status',
        'is_verified',
        'terms_accepted_at',
        'kyc_status',
        'kyc_verified_at',
    ];

    protected $casts = [
        'kyc_verified_at' => 'datetime',
    ];

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

    public function kycVerifications()
    {
        return $this->hasMany(KycVerification::class);
    }

    public function latestKycVerification()
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
    }
}
