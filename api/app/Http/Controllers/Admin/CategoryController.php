<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin-only category CRUD. Equivalent of admin/xulydanhmuc.php, with its
 * raw-interpolated INSERT/UPDATE SQL (built directly from $_POST — a real
 * SQL injection vector) replaced with Eloquent + validated input.
 */
class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return response()->json([
            'data' => $category,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }
}
