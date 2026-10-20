<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Services\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Cart endpoints open to both authenticated customers and guests.
 *
 * Equivalent of frontend/cart.php's `themgiohang` (add), `capnhatsoluong`
 * (update quantity) and `xoa` (remove) actions — fixed to scope every row
 * to the resolved owner (see CartResolver) instead of legacy's single
 * global, unscoped cart shared by every visitor.
 */
class CartController extends Controller
{
    public function __construct(private readonly CartResolver $cartResolver) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->cartResolver->query($request)->orderBy('cart_id')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Add a product to the resolved cart. If it's already there, its
     * quantity is incremented — matching legacy `themgiohang` behavior.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $quantity = $validated['quantity'] ?? 1;

        $existing = $this->cartResolver->query($request)
            ->where('product_id', $product->getKey())
            ->first();

        if ($existing) {
            $existing->increment('product_quantity', $quantity);

            return response()->json(['data' => $existing->refresh()]);
        }

        $item = Cart::create([
            ...$this->cartResolver->ownerAttributes($request),
            'product_id' => $product->getKey(),
            'product_name' => $product->product_name,
            'product_price' => $product->product_discount ?? $product->product_price,
            'product_image' => $product->product_image,
            'product_quantity' => $quantity,
        ]);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Update a cart line's quantity, matching legacy `capnhatsoluong`:
     * a quantity of zero or less removes the line instead.
     */
    public function update(Request $request, Cart $cart): JsonResponse
    {
        $this->ensureOwnedByResolvedCart($request, $cart);

        $validated = $request->validate([
            'quantity' => ['required', 'integer'],
        ]);

        if ($validated['quantity'] <= 0) {
            $cart->delete();

            return response()->json(null, 204);
        }

        $cart->update(['product_quantity' => $validated['quantity']]);

        return response()->json(['data' => $cart]);
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        $this->ensureOwnedByResolvedCart($request, $cart);

        $cart->delete();

        return response()->json(null, 204);
    }

    /**
     * Guard against acting on another customer's/guest's cart row by id.
     */
    private function ensureOwnedByResolvedCart(Request $request, Cart $cart): void
    {
        $owned = $this->cartResolver->query($request)->whereKey($cart->getKey())->exists();

        if (! $owned) {
            throw new NotFoundHttpException;
        }
    }
}
