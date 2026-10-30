<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\IssuesAuthTokens;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    use IssuesAuthTokens;

    private const GUARD = 'admin';

    private const REFRESH_PATH = '/api/admin/refresh';

    /**
     * Equivalent of admin/loginadmin.php. Lazily migrates md5 -> bcrypt.
     * No admin "register" endpoint — legacy admin/register.php is dropped;
     * staff accounts are created through admin CRUD (later phase).
     *
     * Issues an access token (returned in the body) and a refresh token
     * (HttpOnly cookie) — see IssuesAuthTokens. No session login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $staff = Staff::where('user_email', $credentials['email'])->first();

        if (! $staff || ! $staff->verifyAndMigratePassword($credentials['password'])) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản hoặc mật khẩu không đúng.'],
            ])->status(401);
        }

        return $this->tokenResponse(self::GUARD, $staff, self::REFRESH_PATH, $staff);
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
            Staff::class,
            fn (Staff $staff) => $staff,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->revokeTokens($request, self::GUARD, self::REFRESH_PATH);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user('admin'),
        ]);
    }
}
