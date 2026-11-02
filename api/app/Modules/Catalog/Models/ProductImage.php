<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImage extends Model
{
    protected $table = 'product_images';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function urls(): array
    {
        return ['url' => storage_url($this->path), 'md' => storage_url(image_variant($this->path, 'md')), 'sm' => storage_url(image_variant($this->path, 'sm'))];
    }
}
