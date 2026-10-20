<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Public, read-only category listing.
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()->orderBy('category_id')->get(),
        ]);
    }
}
