<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShippingRate extends Model
{
    protected $table = 'shipping_rates';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['province_codes' => 'array'];
    }
}
