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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function customerInfo(): BelongsTo
    {
        return $this->belongsTo(OrderCustomerInfo::class, 'customer_id', 'customer_id');
    }
}
