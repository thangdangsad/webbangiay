<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Admin/staff account management (`quanly_taikhoan`, the "admin" Sanctum
 * guard). Equivalent of admin/taikhoan_admin.php (list), admin/
 * create_account_staff.php (create), admin/edit_admin.php (self profile
 * edit) and admin/change_admin_password.php (self password change).
 * admin/register.php (self-service admin sign-up) is intentionally not
 * replicated — per AdminAuthController's own doc comment, staff accounts
 * are created through this admin CRUD instead.
 *
 * Legacy authorization note: admin/common/checkLogin.php only checks that
 * *some* admin session exists ($_SESSION['admin']); none of taikhoan_admin.php,
 * create_account_staff.php, edit_admin.php or change_admin_password.php
 * distinguish role 1 (admin) from role 2 (staff) anywhere — any logged-in
 * admin or staff account could reach create_account_staff.php and create
 * more accounts. That reads as an oversight rather than intended design
 * (this codebase already fixes a plaintext/unsalted-md5 password bug and a
 * raw-SQL-injection bug elsewhere in these same admin files), so this API
 * deliberately tightens it: creating a staff account, editing *another*
 * account, and resetting *another* account's password are all admin-only
 * (role 1). A staff account may still edit its own profile and change its
 * own password, matching legacy's actual (session-scoped, self-only)
 * edit_admin.php / change_admin_password.php behavior.
 *
 * Legacy never exposed a delete action for these accounts — taikhoan_admin.php
 * has it commented out, same as taikhoan_khachhang.php — so there is no
 * DELETE endpoint here either.
 *
 * create_account_staff.php stored the new password as plain, unhashed text,
 * and (confusingly) into the `quanli_user` customer-login table rather than
 * `quanly_taikhoan` — both clearly bugs. New accounts here are written to
 * `quanly_taikhoan` (the real Staff/admin table), with the password hashed
 * via bcrypt (`Hash::make`).
 */
class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $staff = Staff::query()
            ->orderBy('user_id')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json($this->paginatedResponse($staff));
    }

    public function store(Request $request): JsonResponse
    {
        /** @var Staff $actor */
        $actor = $request->user('admin');

        if ((int) $actor->role !== Staff::ROLE_ADMIN) {
            abort(403, 'Only admin accounts can create staff accounts.');
        }

        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'max:255', 'unique:quanly_taikhoan,user_email'],
            'user_phone' => ['nullable', 'string', 'max:255', 'unique:quanly_taikhoan,user_phone'],
            'user_address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'integer', 'in:'.Staff::ROLE_ADMIN.','.Staff::ROLE_STAFF],
        ]);

        $staff = Staff::create([
            'user_name' => $validated['user_name'],
            'user_email' => $validated['user_email'],
            'user_phone' => $validated['user_phone'] ?? null,
            'user_address' => $validated['user_address'] ?? null,
            'user_password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? Staff::ROLE_STAFF,
        ]);

        return response()->json(['data' => $staff], 201);
    }

    /**
     * Edit a staff profile (name/phone/address only — email is immutable,
     * matching edit_admin.php where the email field is disabled).
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $this->authorizeSelfOrAdmin($request, $staff);

        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_phone' => ['nullable', 'string', 'max:255'],
            'user_address' => ['nullable', 'string', 'max:255'],
        ]);

        $staff->update($validated);

        return response()->json(['data' => $staff]);
    }

    /**
     * Change a staff account's password. Self-service (matching legacy)
     * requires the current password; an admin resetting someone else's
     * password does not need it.
     */
    public function updatePassword(Request $request, Staff $staff): JsonResponse
    {
        $actor = $this->authorizeSelfOrAdmin($request, $staff);
        $isSelf = $actor->is($staff);

        $rules = [
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];

        if ($isSelf) {
            $rules['current_password'] = ['required', 'string'];
        }

        $validated = $request->validate($rules);

        if ($isSelf) {
            if (! $staff->verifyAndMigratePassword($validated['current_password'])) {
                throw ValidationException::withMessages([
                    'current_password' => ['Mật khẩu cũ không đúng.'],
                ]);
            }

            if ($validated['current_password'] === $validated['password']) {
                throw ValidationException::withMessages([
                    'password' => ['Mật khẩu mới không được giống mật khẩu cũ.'],
                ]);
            }
        }

        $staff->forceFill([
            'user_password' => Hash::make($validated['password']),
        ])->save();

        return response()->json(['data' => $staff]);
    }

    /**
     * A staff account may always manage itself; managing another account
     * requires the acting account to be role 1 (admin).
     */
    private function authorizeSelfOrAdmin(Request $request, Staff $target): Staff
    {
        /** @var Staff $actor */
        $actor = $request->user('admin');

        if ($actor->is($target)) {
            return $actor;
        }

        if ((int) $actor->role !== Staff::ROLE_ADMIN) {
            abort(403, 'You may only manage your own account.');
        }

        return $actor;
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
