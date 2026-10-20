<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public, read-only product catalog endpoints.
 *
 * Equivalent of frontend/timkiem.php (search), the category-filtered product
 * grid, frontend/chitietsp.php (detail) and frontend/similarproduct.php
 * (same-category "similar products" block).
 */
class ProductController extends Controller
{
    /**
     * List products, optionally filtered by category and/or a search
     * keyword matched against product_name (legacy timkiem.php searches
     * `product_name LIKE '%keyword%'`).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with('category')
            ->when($validated['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($validated['q'] ?? null, function ($query, $keyword) {
                $query->where('product_name', 'like', '%'.$keyword.'%');
            })
            ->orderBy('product_id')
            ->paginate($validated['per_page'] ?? 12);

        return response()->json($this->paginatedResponse($products));
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $product->load('category'),
        ]);
    }

    /**
     * Same-category "similar products" block, matching legacy
     * similarproduct.php: same category_id, excluding the product itself,
     * limited to 4.
     */
    public function similar(Product $product): JsonResponse
    {
        $similar = Product::query()
            ->where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->limit(4)
            ->get();

        return response()->json([
            'data' => $similar,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paginatedResponse(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
