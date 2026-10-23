<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\OrderCustomerInfo;
use App\Models\OrderTransaction;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes orders from a resolved cart. Legacy `thanhtoan.php` duplicates this
 * order-writing logic three times, once per payment branch (COD/Momo/VNPay/
 * PayPal) with copy-pasted SQL. This service centralizes it behind one
 * method per payment flow so a later phase can add createMomoOrder() etc.
 * without repeating the customer/order/transaction-row bookkeeping.
 */
class OrderService
{
    /**
     * Create a Cash-On-Delivery order from the given cart lines.
     *
     * Writes one `customer` (shipping info) row, one `donhang` (order) row
     * and one `giaodich` (transaction) row per cart line, all inside a
     * single DB transaction — matching legacy's per-cart-line insert
     * pattern in thanhtoan.php's COD branch, minus its raw-SQL and
     * global-cart bugs.
     *
     * @param  array{name: string, phone: string, email: string, address: string, note: ?string}  $shippingInfo
     * @param  Collection<int, Cart>  $cartItems
     * @return array{customer_info: OrderCustomerInfo, mahang: string, total_payment: float}
     */
    public function createCodOrder(array $shippingInfo, Collection $cartItems, ?CustomerAccount $account = null): array
    {
        return DB::transaction(function () use ($shippingInfo, $cartItems, $account) {
            $this->reserveStock($cartItems);

            $userCode = $this->resolveUserCode($account);

            $customerInfo = OrderCustomerInfo::create([
                'customer_name' => $shippingInfo['name'],
                'user_code' => $userCode,
                'customer_phone' => $shippingInfo['phone'],
                'customer_email' => $shippingInfo['email'],
                'customer_address' => $shippingInfo['address'],
                'payments' => 0,
                'customer_note' => $shippingInfo['note'] ?? null,
            ]);

            $mahang = (string) random_int(1000, 9999);
            $today = now()->toDateString();
            $totalPayment = $cartItems->sum(
                fn (Cart $item): float => (float) $item->product_quantity * (float) $item->product_price
            );

            foreach ($cartItems as $item) {
                Order::create([
                    'product_id' => $item->variant_id,
                    'customer_id' => $customerInfo->getKey(),
                    'soluong' => $item->product_quantity,
                    'tongDoanhThu' => $totalPayment,
                    'mahang' => $mahang,
                    'ngayDatHang' => $today,
                    'payments' => 0,
                ]);

                OrderTransaction::create([
                    'khachhang_id' => $customerInfo->getKey(),
                    'sanpham_id' => $item->variant_id,
                    'soluong' => $item->product_quantity,
                    'magiaodich' => $mahang,
                    'ngayThangNam' => $today,
                ]);
            }

            return [
                'customer_info' => $customerInfo,
                'mahang' => $mahang,
                'total_payment' => $totalPayment,
            ];
        });
    }

    /**
     * Lock and validate stock for every distinct variant in the cart, then
     * decrement it — all inside the caller's DB transaction.
     *
     * Two customers checking out the same low-stock variant at the same
     * moment is a real race: without locking, both requests could read the
     * same "1 left" quantity and both succeed, overselling it. `lockForUpdate`
     * takes a row lock on each `product_variant` row (SELECT ... FOR UPDATE),
     * so a second concurrent checkout touching the same variant blocks until
     * the first transaction commits (stock decremented) or rolls back (stock
     * check failed) — it can never read a stale quantity mid-flight.
     *
     * Variants are locked in a fixed order (sorted by variant_id) so that
     * two checkouts sharing several variants always acquire their locks in
     * the same order, which avoids a deadlock (A locks variant 1 then waits
     * on 2, while B locks variant 2 then waits on 1).
     *
     * @param  Collection<int, Cart>  $cartItems
     */
    private function reserveStock(Collection $cartItems): void
    {
        $requestedByVariant = $cartItems
            ->groupBy('variant_id')
            ->map(fn (Collection $lines): int => (int) $lines->sum('product_quantity'));

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('variant_id', $requestedByVariant->keys())
            ->orderBy('variant_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('variant_id');

        $insufficient = [];

        foreach ($requestedByVariant as $variantId => $requestedQuantity) {
            $variant = $variants->get($variantId);

            if (! $variant || (int) $variant->product_quantity < $requestedQuantity) {
                $insufficient[] = $variant
                    ? "{$variant->product->product_name} (size {$variant->size})"
                    : "#{$variantId}";
            }
        }

        if ($insufficient !== []) {
            throw ValidationException::withMessages([
                'cart' => ['Sản phẩm sau không đủ số lượng trong kho: '.implode(', ', $insufficient).'.'],
            ]);
        }

        foreach ($requestedByVariant as $variantId => $requestedQuantity) {
            $variants->get($variantId)->decrement('product_quantity', $requestedQuantity);
        }
    }

    /**
     * Legacy assigns a random 4-digit `user_code` to a logged-in customer
     * the first time they check out (when it's still the default 0), then
     * keeps reusing it on every later order.
     */
    private function resolveUserCode(?CustomerAccount $account): ?int
    {
        if (! $account) {
            return null;
        }

        if ((int) $account->user_code !== 0) {
            return (int) $account->user_code;
        }

        $userCode = random_int(1000, 9999);
        $account->forceFill(['user_code' => $userCode])->save();

        return $userCode;
    }
}
