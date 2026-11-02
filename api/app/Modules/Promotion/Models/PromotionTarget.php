<?php

namespace App\Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromotionTarget extends Model
{
    protected $table = 'promotion_targets';
    public $timestamps = false;
    protected $guarded = ['id'];
}
