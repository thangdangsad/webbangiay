<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `product_variant` table: one row per shoe size. This is the old
 * `product` table, renamed in place (see
 * 2026_10_23_090000_rename_product_to_product_variant.php) — its primary
 * key keeps the exact same values and auto-increment counter it always
 * had, just renamed from `product_id` to `variant_id`.
 */
#[Fillable([
    'product_id',
    'size',
    'product_quantity',
    'product_sold',
    'is_active',
])]
class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variant';

    protected $primaryKey = 'variant_id';

    protected function casts(): array
    {
        return [
            'product_quantity' => 'integer',
            'product_sold' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
