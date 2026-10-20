<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `customer` table: shipping/billing info snapshot captured per order at
 * checkout time. Not the login account — see CustomerAccount (`quanli_user`).
 */
#[Fillable([
    'customer_name',
    'user_code',
    'customer_phone',
    'customer_email',
    'customer_address',
    'payments',
    'customer_note',
])]
class OrderCustomerInfo extends Model
{
    use HasFactory;

    protected $table = 'customer';

    protected $primaryKey = 'customer_id';

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransaction::class, 'khachhang_id', 'customer_id');
    }
}
