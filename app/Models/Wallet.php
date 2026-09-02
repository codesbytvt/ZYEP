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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
