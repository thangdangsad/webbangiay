<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** `giaodich` table. */
#[Fillable([
    'khachhang_id',
    'sanpham_id',
    'soluong',
    'magiaodich',
    'ngayThangNam',
])]
class OrderTransaction extends Model
{
    use HasFactory;

    protected $table = 'giaodich';

    protected $primaryKey = 'giaodich_id';

    public function customerInfo(): BelongsTo
    {
        return $this->belongsTo(OrderCustomerInfo::class, 'khachhang_id', 'customer_id');
    }

    /**
     * Column stays named `sanpham_id` for historical reasons (unchanged by
     * the product/variant split); it stores a `product_variant.variant_id`
     * value.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'sanpham_id', 'variant_id');
    }
}
