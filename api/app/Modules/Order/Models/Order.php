<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'orders';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ship_address' => 'array', 'placed_at' => 'datetime', 'cancelled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function history(): HasMany { return $this->hasMany(OrderStatusHistory::class)->orderBy('id'); }
    public function payments(): HasMany { return $this->hasMany(\App\Modules\Payment\Models\Payment::class); }
    public function shipment(): HasOne { return $this->hasOne(\App\Modules\Shipping\Models\Shipment::class); }
    public function returns(): HasMany { return $this->hasMany(ReturnRequest::class); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Modules\User\Models\User::class); }
}
