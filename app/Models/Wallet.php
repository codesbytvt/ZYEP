<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'mwallets';

    protected $fillable = [
        'user_id',
        'balance_credits',
        'lifetime_purchased_credits',
        'lifetime_spent_credits',
    ];

    // Mirrors the DB column defaults: firstOrCreate()/create() build the model
    // in memory from just the given attributes and never re-fetch it, so
    // without this a freshly created wallet reports null instead of 0 here.
    protected $attributes = [
        'balance_credits' => 0,
        'lifetime_purchased_credits' => 0,
        'lifetime_spent_credits' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
