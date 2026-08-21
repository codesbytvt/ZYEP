<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        // Get only top-level categories with their subcategories
        $categories = Category::with('subcategories')
            ->whereNull('parent_id')
            ->where('status', 1)
            ->get();

        return response()->json(['categories' => $categories]);
    }
}
