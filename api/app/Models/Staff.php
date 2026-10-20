<?php

namespace App\Models;

use App\Concerns\LazilyMigratesLegacyPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `quanly_taikhoan` table — admin/staff LOGIN account (the "admin" Sanctum guard).
 * role: 1 = admin, 2 = staff.
 */
#[Fillable(['user_name', 'user_code', 'user_email', 'user_phone', 'user_address', 'user_password', 'role'])]
#[Hidden(['user_password'])]
class Staff extends Authenticatable
{
    use HasFactory, LazilyMigratesLegacyPassword, Notifiable;

    protected $table = 'quanly_taikhoan';

    protected $primaryKey = 'user_id';

    public const ROLE_ADMIN = 1;

    public const ROLE_STAFF = 2;

    protected function casts(): array
    {
        return [
            'user_password' => 'hashed',
            'role' => 'integer',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'user_password';
    }

    /**
     * Legacy admin panel hashed passwords with unsalted md5(). Still honored
     * here so existing accounts keep working until they log in again and get
     * transparently migrated to bcrypt.
     */
    public function legacyPasswordMatches(string $plainPassword): bool
    {
        return $this->user_password === md5($plainPassword);
    }
}
