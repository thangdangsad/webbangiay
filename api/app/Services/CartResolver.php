<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CustomerAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolves which cart rows "belong" to the current visitor, fixing the
 * legacy `cart.php` bug where every visitor shared a single global cart
 * (no scoping by customer or session at all).
 *
 * An authenticated customer's cart is scoped by `customer_id`. A guest's
 * cart is scoped by a `guest_token` UUID persisted server-side in the
 * Laravel session (never trusted from the client), created lazily the
 * first time the guest touches the cart.
 */
class CartResolver
{
    private const SESSION_KEY = 'guest_cart_token';

    /**
     * Build the base query for the resolved cart owner (customer or guest).
     */
    public function query(Request $request): Builder
    {
        return Cart::query()->where($this->ownerConstraints($request));
    }

    /**
     * The where() constraints identifying the resolved cart owner.
     *
     * @return array<string, mixed>
     */
    public function ownerConstraints(Request $request): array
    {
        $customer = $request->user('customer');

        if ($customer) {
            return ['customer_id' => $customer->getKey()];
        }

        return ['guest_token' => $this->guestToken($request)];
    }

    /**
     * Fillable "owner" attributes to stamp onto a newly created cart row.
     *
     * @return array<string, mixed>
     */
    public function ownerAttributes(Request $request): array
    {
        $customer = $request->user('customer');

        if ($customer) {
            return ['customer_id' => $customer->getKey(), 'guest_token' => null];
        }

        return ['customer_id' => null, 'guest_token' => $this->guestToken($request)];
    }

    /**
     * Get (or lazily create) this guest's session-scoped cart token.
     */
    public function guestToken(Request $request): string
    {
        $token = $request->session()->get(self::SESSION_KEY);

        if (! is_string($token)) {
            $token = (string) Str::uuid();
            $request->session()->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Merge a guest's session-scoped cart rows into a customer account that
     * just logged in: the rows switch ownership from guest_token to
     * customer_id, and the guest token is forgotten from the session.
     */
    public function mergeGuestCartIntoCustomer(Request $request, CustomerAccount $customer): void
    {
        $token = $request->session()->get(self::SESSION_KEY);

        if (! is_string($token)) {
            return;
        }

        Cart::query()
            ->where('guest_token', $token)
            ->update(['customer_id' => $customer->getKey(), 'guest_token' => null]);

        $request->session()->forget(self::SESSION_KEY);
    }
}
