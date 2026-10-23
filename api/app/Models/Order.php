<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** `donhang` table. */
#[Fillable([
    'product_id',
    'customer_id',
    'soluong',
    'tongDoanhThu',
    'mahang',
    'ngayDatHang',
    'trangThai',
    'payments',
])]
class Order extends Model
{
    use HasFactory;

    protected $table = 'donhang';

    protected $primaryKey = 'order_id';

    /**
     * Column stays named `product_id` for historical reasons (unchanged by
     * the product/variant split); it stores a `product_variant.variant_id`
     * value.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_id', 'variant_id');
    }

    public function customerInfo(): BelongsTo
    {
        return $this->belongsTo(OrderCustomerInfo::class, 'customer_id', 'customer_id');
    }
}
