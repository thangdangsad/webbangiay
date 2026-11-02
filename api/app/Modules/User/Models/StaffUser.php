<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Redis;

class StaffUser extends Authenticatable
{
    protected $table = 'staff_users';

    protected $fillable = ['email', 'password_hash', 'full_name', 'role_id', 'status', 'last_login_at', 'two_factor_secret'];

    protected $hidden = ['password_hash', 'two_factor_secret'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime'];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Quyền đọc từ Redis perm:staff:{id} (TTL 5 phút), xóa khi đổi vai trò. */
    public function permissions(): array
    {
        $key = "perm:staff:{$this->id}";
        $redis = Redis::connection('auth');
        $perms = $redis->smembers($key);
        if (! $perms) {
            $perms = $this->role->permissions()->pluck('code')->all();
            if ($perms) {
                $redis->sadd($key, $perms);
                $redis->expire($key, 300);
            }
        }

        return $perms;
    }

    public function hasPermission(string $code): bool
    {
        return in_array($code, $this->permissions(), true);
    }

    public static function forgetPermissions(int $id): void
    {
        Redis::connection('auth')->del("perm:staff:{$id}");
    }
}
