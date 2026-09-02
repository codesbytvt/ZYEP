<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $table = 'twallet_transactions';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'credits',
        'balance_after',
        'reference_type',
        'reference_id',
        'payment_id',
        'status',
        'note',
        'created_by',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
