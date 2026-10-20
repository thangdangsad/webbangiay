<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * `voucher` table. Present in the legacy schema but not written by any live
 * legacy code path (the insert was commented out) — kept for schema parity
 * with the production dump and for a future voucher feature.
 */
#[Fillable([
    'code',
    'discount',
    'min_quantity',
    'used',
    'user_id',
])]
class Voucher extends Model
{
    use HasFactory;

    protected $table = 'voucher';

    protected $primaryKey = 'voucher_id';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'discount' => 'decimal:2',
            'used' => 'boolean',
        ];
    }
}
