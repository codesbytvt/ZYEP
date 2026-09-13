<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use Illuminate\Http\Request;

class CreditPackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = CreditPackage::where('is_active', true)->get();

        return response()->json([
            'packages' => $packages,
        ]);
    }
}
