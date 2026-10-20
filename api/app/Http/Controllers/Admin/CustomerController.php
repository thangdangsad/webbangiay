<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Admin customer ACCOUNT management (the `quanli_user` login table — see
 * CustomerAccount). Equivalent of admin/taikhoan_khachhang.php, which is
 * read-only in legacy: it only lists accounts, and its edit/delete links
 * are commented out in the markup. This stays read-only for the same
 * reason — no create/update/delete endpoints here.
 *
 * Note: admin/customer.php is a *different* legacy feature despite the
 * similar name — it browses the per-order `customer` shipping-info
 * snapshots (OrderCustomerInfo) grouped by transaction (`giaodich`), which
 * is about orders, not login accounts, and is already covered by the
 * Orders detail endpoint. It is not replicated here.
 */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = CustomerAccount::query()
            ->orderBy('user_id')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json($this->paginatedResponse($customers));
    }

    /**
     * Customer account detail, with order history resolved via the shared
     * `user_code` link to OrderCustomerInfo rows — the same soft link
     * OrderService::resolveUserCode() writes at checkout time (not a real
     * FK, just like legacy). A user_code of 0/null means the account has
     * never checked out, so order history is empty rather than querying.
     */
    public function show(CustomerAccount $customer): JsonResponse
    {
        $hasOrderHistory = ! empty($customer->user_code) && (int) $customer->user_code !== 0;

        return response()->json([
            'data' => $customer,
            'order_history' => $hasOrderHistory
                ? $customer->orderHistory()->with(['orders.product', 'transactions.product'])->get()
                : [],
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
