<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Equivalent of admin/loginadmin.php. Lazily migrates md5 -> bcrypt.
     * No admin "register" endpoint — legacy admin/register.php is dropped;
     * staff accounts are created through admin CRUD (later phase).
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

        $request->session()->regenerate();
        Auth::guard('admin')->login($staff);

        return response()->json([
            'data' => $staff,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user('admin'),
        ]);
    }
}
