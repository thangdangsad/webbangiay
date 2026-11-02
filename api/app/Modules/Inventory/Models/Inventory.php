<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inventory extends Model
{
    protected $table = 'inventory';
    public $incrementing = false; protected $primaryKey = 'variant_id';
    protected $guarded = ['id'];

    public function variant(): BelongsTo { return $this->belongsTo(\App\Modules\Catalog\Models\ProductVariant::class, 'variant_id'); }
    public function getAvailableAttribute(): int { return $this->on_hand - $this->reserved; }
}
