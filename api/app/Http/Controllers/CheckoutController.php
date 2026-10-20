<?php

namespace App\Http\Controllers;

use App\Services\CartResolver;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Checkout endpoint — COD only for this phase (see OrderService). Equivalent
 * of frontend/thanhtoan.php's `payments == "0"` branch; the Momo/VNPay/
 * PayPal branches are a later phase.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartResolver $cartResolver,
        private readonly OrderService $orderService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            // only Cash-On-Delivery is implemented in this phase.
            'payments' => ['nullable', 'in:0'],
        ]);

        if (($validated['payments'] ?? '0') !== '0') {
            throw ValidationException::withMessages([
                'payments' => ['Chỉ hỗ trợ thanh toán khi nhận hàng (COD) ở giai đoạn này.'],
            ]);
        }

        $cartItems = $this->cartResolver->query($request)->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Giỏ hàng của bạn đang trống.'],
            ]);
        }

        $account = $request->user('customer');

        $result = $this->orderService->createCodOrder([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'note' => $validated['note'] ?? null,
        ], $cartItems, $account);

        $this->cartResolver->query($request)->delete();

        return response()->json([
            'data' => [
                'customer' => $result['customer_info'],
                'mahang' => $result['mahang'],
                'total_payment' => $result['total_payment'],
            ],
        ], 201);
    }
}
