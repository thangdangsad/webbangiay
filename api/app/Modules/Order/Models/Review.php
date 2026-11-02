<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $table = 'product_reviews';
    protected $guarded = ['id'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Modules\User\Models\User::class); }
}
