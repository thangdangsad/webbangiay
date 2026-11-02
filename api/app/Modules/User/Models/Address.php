<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'receiver_name', 'phone', 'province_code', 'district_code', 'ward_code', 'street', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
}
