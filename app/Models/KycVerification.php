<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    protected $table = 'tkyc_verifications';

    protected $fillable = [
        'provider_id',
        'user_id',
        'method',
        'vendor',
        'vendor_reference_id',
        'status',
        'masked_aadhaar',
        'verified_name',
        'raw_response',
        'attempt_count',
        'initiated_at',
        'verified_at',
        'expires_at',
    ];

    protected $casts = [
        // Encrypted at rest — this column holds the vendor's raw API response
        // (an array/JSON payload, hence 'encrypted:array' rather than plain
        // 'encrypted', which only handles scalar strings) and must never be
        // readable directly from a DB dump or backup.
        'raw_response' => 'encrypted:array',
        'initiated_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Never expose the vendor payload or masked Aadhaar over the API by default.
    protected $hidden = ['raw_response'];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
