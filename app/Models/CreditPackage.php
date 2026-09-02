<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $table = 'mcredit_packages';

    protected $fillable = [
        'name',
        'credits',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
