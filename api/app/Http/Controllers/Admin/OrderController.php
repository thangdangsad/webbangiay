<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Admin order management. Equivalent of admin/donhang.php (list + detail +
 * status transitions). admin/unconfirmed_order.php is the same feature
 * pre-filtered to trangThai = 0 — exposed here as `GET /api/admin/orders
 * ?status=0` rather than as a separate endpoint.
 *
 * Status vocabulary (`donhang.trangThai`), read directly from legacy's own
 * transitions (xac_madonhang / huy_madonhang / huy_xacnhan_madonhang):
 *   0 = chờ xác nhận (pending) — legacy also stores this as NULL for fresh
 *       COD orders (OrderService::createCodOrder() never sets trangThai) and
 *       relies on PHP's loose `== 0` comparison to treat NULL the same way.
 *   1 = đã xác nhận (confirmed)
 *   2 = đã hủy (cancelled)
 *
 * A single checkout writes one `donhang` row per cart line, all sharing the
 * same `customer_id` (the `customer` shipping-info row written once per
 * checkout — see OrderService::createCodOrder()). Legacy groups/updates
 * these by `mahang`, a random 4-digit code with no real uniqueness
 * guarantee; this controller uses the real `customer_id` foreign key as the
 * order-grouping key instead, which is equivalent in practice but
 * collision-proof. The list below intentionally stays one row per
 * `donhang` line (not deduplicated the way legacy's fragile `GROUP BY
 * mahang` display trick does) — each line already carries full product +
 * customer info, and the detail endpoint shows the full sibling group.
 */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with(['variant.product', 'customerInfo'])
            ->when(isset($validated['status']), function ($query) use ($validated) {
                $status = (int) $validated['status'];

                if ($status === 0) {
                    $query->where(function ($q) {
                        $q->whereNull('trangThai')->orWhere('trangThai', 0);
                    });
                } else {
                    $query->where('trangThai', $status);
                }
            })
            ->orderByDesc('order_id')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json($this->paginatedResponse($orders));
    }

    /**
     * Order detail: the requested line, its sibling lines from the same
     * checkout (same customer_id) and the matching transaction rows.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load([
            'variant.product',
            'customerInfo.orders.variant.product',
            'customerInfo.transactions.variant.product',
        ]);

        return response()->json(['data' => $order]);
    }

    /**
     * Update order status. Applies to every `donhang` line from the same
     * checkout (same customer_id), not just the single line the route was
     * called with, matching legacy's intent of confirming/cancelling a
     * whole order at once (legacy did this via `mahang`; this uses the
     * real FK instead).
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'trangThai' => ['required', 'integer', 'in:0,1,2'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            Order::where('customer_id', $order->customer_id)
                ->update(['trangThai' => $validated['trangThai']]);
        });

        return response()->json([
            'data' => $order->refresh()->load(['variant.product', 'customerInfo']),
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
