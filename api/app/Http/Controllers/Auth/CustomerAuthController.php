<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\IssuesAuthTokens;
use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Services\CartResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    use IssuesAuthTokens;

    private const GUARD = 'customer';

    private const REFRESH_PATH = '/api/refresh';

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
     *
     * Issues an access token (returned in the body) and a refresh token
     * (HttpOnly cookie) — see IssuesAuthTokens. No session login.
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

        $cartResolver->mergeGuestCartIntoCustomer($request, $customer);

        return $this->tokenResponse(self::GUARD, $customer, self::REFRESH_PATH, $customer);
    }

    /**
     * Exchange a still-valid refresh-token cookie for a fresh access +
     * refresh token pair. Called by the SPA when a request comes back 401
     * (access token expired) or on app load to restore the session.
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->handleRefresh(
            $request,
            self::GUARD,
            self::REFRESH_PATH,
            CustomerAccount::class,
            fn (CustomerAccount $customer) => $customer,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->revokeTokens($request, self::GUARD, self::REFRESH_PATH);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user('customer'),
        ]);
    }
}
