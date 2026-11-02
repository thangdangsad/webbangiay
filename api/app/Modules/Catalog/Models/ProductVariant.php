<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected $table = 'product_variants';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function color(): BelongsTo { return $this->belongsTo(Color::class); }
    public function size(): BelongsTo { return $this->belongsTo(Size::class); }
    public function inventory(): HasOne { return $this->hasOne(\App\Modules\Inventory\Models\Inventory::class, 'variant_id'); }
}
