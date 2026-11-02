<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $table = 'order_items';
    public $timestamps = false;
    protected $guarded = ['id'];
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
