<?php

namespace App\Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CouponRedemption extends Model
{
    protected $table = 'coupon_redemptions';
    protected $guarded = ['id'];
}
