<?php

namespace App\Models;

use App\Concerns\LazilyMigratesLegacyPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `quanli_user` table — the customer LOGIN account (the "customer" Sanctum guard).
 * Not to be confused with the `customer` table (see OrderCustomerInfo), which
 * stores shipping/billing info captured per order, not login credentials.
 */
#[Fillable(['user_name', 'user_code', 'user_email', 'user_phone', 'user_address', 'user_password', 'role'])]
#[Hidden(['user_password'])]
class CustomerAccount extends Authenticatable
{
    use HasFactory, LazilyMigratesLegacyPassword, Notifiable;

    protected $table = 'quanli_user';

    protected $primaryKey = 'user_id';

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
     * Order history for this login account, resolved via the shared
     * `user_code` link that OrderService::resolveUserCode() writes onto
     * both this row and the `customer` (OrderCustomerInfo) row at checkout
     * time. Not a real foreign key — just like legacy, it's a soft link
     * through a random 4-digit code, so `user_code` 0/null (never checked
     * out yet) must be excluded by the caller.
     */
    public function orderHistory(): HasMany
    {
        return $this->hasMany(OrderCustomerInfo::class, 'user_code', 'user_code');
    }

    /**
     * Legacy frontend compared passwords with plain `==` (plaintext, a real
     * security bug). Still honored here so existing accounts keep working
     * until they log in again and get transparently migrated to bcrypt.
     */
    public function legacyPasswordMatches(string $plainPassword): bool
    {
        return $this->user_password === $plainPassword;
    }
}
