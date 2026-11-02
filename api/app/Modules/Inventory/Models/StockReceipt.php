<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StockReceipt extends Model
{
    protected $table = 'stock_receipts';
    protected $guarded = ['id'];

    public function items(): HasMany { return $this->hasMany(StockReceiptItem::class); }
}
