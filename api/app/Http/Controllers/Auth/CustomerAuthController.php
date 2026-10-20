<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Services\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    /**
     * Equivalent of frontend/topbar.php's `register` branch
     * (register_name/register_email/register_phone/register_address/register_password).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:quanli_user,user_email'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $customer = CustomerAccount::create([
            'user_name' => $data['name'],
            'user_email' => $data['email'],
            'user_phone' => $data['phone'],
            'user_address' => $data['address'],
            'user_password' => Hash::make($data['password']),
            'user_code' => 0,
        ]);

        return response()->json([
            'data' => $customer,
        ], 201);
    }

    /**
     * Equivalent of frontend/topbar.php's `login_home` branch, minus the
     * admin-via-customer-table hack. Lazily migrates plaintext -> bcrypt.
     * Also merges any session-scoped guest cart into the now-authenticated
     * customer's account (see CartResolver::mergeGuestCartIntoCustomer()).
     */
    public function login(Request $request, CartResolver $cartResolver): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = CustomerAccount::where('user_email', $credentials['email'])->first();

        if (! $customer || ! $customer->verifyAndMigratePassword($credentials['password'])) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ])->status(401);
        }

        $request->session()->regenerate();
        Auth::guard('customer')->login($customer);
        $cartResolver->mergeGuestCartIntoCustomer($request, $customer);

        return response()->json([
            'data' => $customer,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user('customer'),
        ]);
    }
}
