<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\TokenService;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\StaffUser;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Cài đặt cửa hàng, nhân viên, vai trò, nhật ký hoạt động. */
class AdminSystemController
{
    public function settings()
    {
        $out = [];
        foreach (Settings::groups() as $g) {
            $out[$g] = Settings::get($g) + ($g === 'store' ? ['logo_url' => storage_url(Settings::get('store.logo_path')), 'favicon_url' => storage_url(Settings::get('store.favicon_path'))] : []);
        }
        $out['momo'] = ['configured' => (bool) config('shoe.momo.partner_code'), 'fake' => (bool) config('shoe.momo.fake'), 'endpoint' => config('shoe.momo.endpoint'), 'partner_code' => config('shoe.momo.partner_code')];

        return ['data' => $out];
    }

    public function updateSettings(Request $r)
    {
        $v = $r->validate([
            'store' => 'sometimes|array', 'store.name' => 'sometimes|string|max:120', 'store.phone' => 'sometimes|nullable|string|max:20', 'store.email' => 'sometimes|nullable|email',
            'store.address' => 'sometimes|nullable|string|max:255', 'store.logo_path' => 'sometimes|nullable|string', 'store.favicon_path' => 'sometimes|nullable|string',
            'payment' => 'sometimes|array', 'payment.cod_enabled' => 'sometimes|boolean', 'payment.momo_enabled' => 'sometimes|boolean', 'payment.momo_max_amount' => 'sometimes|integer|min:0',
            'shipping' => 'sometimes|array', 'shipping.free_threshold' => 'sometimes|integer|min:0', 'shipping.default_fee' => 'sometimes|integer|min:0', 'shipping.carrier' => 'sometimes|string|max:60',
            'order' => 'sometimes|array', 'order.return_days' => 'sometimes|integer|min:0|max:60', 'order.momo_expire_minutes' => 'sometimes|integer|min:5|max:120',
        ]);
        $images = app(\App\Modules\Catalog\Services\ImageStore::class);
        foreach ($v as $group => $values) {
            if ($group === 'store') {
                foreach (['logo_path', 'favicon_path'] as $f) {
                    if (array_key_exists($f, $values)) {
                        $values[$f] = $images->commit($values[$f], 'settings');
                    }
                }
            }
            Settings::put($group, $values);
        }

        return $this->settings();
    }

    // ---- Staff ----
    public function staffIndex()
    {
        return ['data' => StaffUser::with('role')->orderBy('id')->get()->map(fn ($s) => $this->staffRow($s))];
    }

    private function staffRow(StaffUser $s): array
    {
        return ['id' => $s->id, 'email' => $s->email, 'full_name' => $s->full_name, 'status' => $s->status, 'role_id' => $s->role_id, 'role' => $s->role?->name,
            'role_code' => $s->role?->code, 'last_login_at' => $s->last_login_at, 'two_factor_enabled' => (bool) $s->two_factor_secret];
    }

    public function staffStore(Request $r)
    {
        $v = $r->validate(['email' => 'required|email|unique:staff_users,email', 'full_name' => 'required|string|max:120', 'role_id' => 'required|exists:roles,id', 'password' => 'required|string|min:8']);
        $s = StaffUser::create(['email' => strtolower($v['email']), 'full_name' => $v['full_name'], 'role_id' => $v['role_id'], 'password_hash' => Hash::make($v['password'])]);

        return response()->json(['data' => $this->staffRow($s->load('role'))], 201);
    }

    public function staffUpdate(Request $r, int $id)
    {
        $s = StaffUser::findOrFail($id);
        $v = $r->validate([
            'full_name' => 'sometimes|string|max:120', 'role_id' => 'sometimes|exists:roles,id', 'status' => 'sometimes|in:active,blocked',
            'password' => 'sometimes|nullable|string|min:8', 'reset_2fa' => 'sometimes|boolean',
        ]);
        if ($s->id === $r->user('staff')->id && (($v['status'] ?? 'active') !== 'active' || (isset($v['role_id']) && $v['role_id'] != $s->role_id))) {
            throw ApiException::invalid('Không thể tự khóa hoặc đổi vai trò của chính mình');
        }
        if (! empty($v['password'])) {
            $s->password_hash = Hash::make($v['password']);
        }
        if (! empty($v['reset_2fa'])) {
            $s->two_factor_secret = null;
        }
        $s->fill(collect($v)->only(['full_name', 'role_id', 'status'])->all())->save();
        StaffUser::forgetPermissions($s->id);
        if (isset($v['status']) && $v['status'] !== 'active' || ! empty($v['password']) || isset($v['role_id'])) {
            TokenService::for('staff')->revokeAll($s->id);
        }

        return ['data' => $this->staffRow($s->refresh()->load('role'))];
    }

    // ---- Roles ----
    public function roles()
    {
        return ['data' => Role::with('permissions')->get()->map(fn ($r) => ['id' => $r->id, 'code' => $r->code, 'name' => $r->name, 'permissions' => $r->permissions->pluck('code')->values()]),
            'permissions' => Permission::orderBy('code')->pluck('code')];
    }

    public function roleUpdate(Request $r, int $id)
    {
        $role = Role::findOrFail($id);
        if ($role->code === 'admin') {
            throw ApiException::invalid('Không thể thay đổi quyền của vai trò Admin');
        }
        $v = $r->validate(['name' => 'sometimes|string|max:80', 'permissions' => 'required|array', 'permissions.*' => 'string|exists:permissions,code']);
        if (isset($v['name'])) {
            $role->update(['name' => $v['name']]);
        }
        $role->permissions()->sync(Permission::whereIn('code', $v['permissions'])->pluck('id'));
        foreach (StaffUser::where('role_id', $role->id)->pluck('id') as $sid) {
            StaffUser::forgetPermissions($sid);
        }

        return $this->roles();
    }

    // ---- Audit ----
    public function auditLogs(Request $r)
    {
        $q = DB::table('audit_logs as a')->leftJoin('staff_users as s', 's.id', '=', 'a.staff_id')->orderByDesc('a.id')->select('a.*', 's.full_name as staff_name');
        if ($r->filled('staff_id')) {
            $q->where('a.staff_id', $r->query('staff_id'));
        }
        if ($r->filled('entity')) {
            $q->where('a.entity', $r->query('entity'));
        }
        if ($s = $r->query('q')) {
            $q->where(fn ($w) => $w->where('a.entity', 'like', "%$s%")->orWhere('a.action', 'like', "%$s%")->orWhere('s.full_name', 'like', "%$s%"));
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 30)));

        return ['data' => collect($page->items())->map(fn ($a) => [
            'id' => $a->id, 'staff' => $a->staff_name ?? 'Hệ thống', 'action' => $a->action, 'entity' => $a->entity, 'entity_id' => $a->entity_id,
            'after' => $a->after ? json_decode($a->after, true) : null, 'ip' => $a->ip, 'created_at' => $a->created_at,
        ]), 'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }
}
