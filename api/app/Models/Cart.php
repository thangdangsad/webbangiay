<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'variant_id',
    'product_name',
    'product_price',
    'product_image',
    'product_quantity',
    'size',
    'customer_id',
    'guest_token',
])]
class Cart extends Model
{
    use HasFactory;

    protected $table = 'cart';

    protected $primaryKey = 'cart_id';

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'variant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_id', 'user_id');
    }
}
