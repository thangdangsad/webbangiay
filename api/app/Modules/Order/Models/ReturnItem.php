<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReturnItem extends Model
{
    protected $table = 'return_items';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['restock' => 'boolean'];
    }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
