<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** `tbl_momo` table — raw log of MoMo redirect/IPN payloads. */
#[Fillable([
    'partner_Code',
    'order_Id',
    'amount',
    'order_Info',
    'order_Type',
    'trans_Id',
    'pay_type',
    'code_cart',
])]
class MomoLog extends Model
{
    protected $table = 'tbl_momo';

    protected $primaryKey = 'id_momo';
}
