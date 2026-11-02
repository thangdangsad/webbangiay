<?php

namespace App\Modules\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = ['email', 'phone', 'password_hash', 'full_name', 'gender', 'birthday', 'status'];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'birthday' => 'date:Y-m-d'];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(\App\Modules\Order\Models\Order::class);
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
}
