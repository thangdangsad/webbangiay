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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'sanpham_id', 'product_id');
    }
}
