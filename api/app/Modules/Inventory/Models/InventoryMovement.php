<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function variant(): BelongsTo { return $this->belongsTo(\App\Modules\Catalog\Models\ProductVariant::class, 'variant_id'); }
}
