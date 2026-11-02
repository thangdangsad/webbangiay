<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $table = 'payments';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['raw_payload' => 'array', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];
    }
    public function order(): BelongsTo { return $this->belongsTo(\App\Modules\Order\Models\Order::class); }
}
