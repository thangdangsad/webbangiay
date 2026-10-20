<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_name',
    'size',
    'product_image',
    'product_quantity',
    'product_price',
    'product_discount',
    'product_details',
    'product_description',
    'product_acitve',
    'product_hot',
    'product_sold',
    'category_id',
])]
class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $primaryKey = 'product_id';

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:0',
            'product_discount' => 'decimal:0',
            'product_quantity' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
}
