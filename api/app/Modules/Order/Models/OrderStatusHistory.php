<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';
    public $timestamps = false;
    protected $guarded = ['id'];
}
