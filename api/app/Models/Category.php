<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_name'])]
class Category extends Model
{
    use HasFactory;

    protected $table = 'tbl_category';

    protected $primaryKey = 'category_id';

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
