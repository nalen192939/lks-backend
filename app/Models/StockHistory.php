<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $fillable = [
        'item_type',
        'item_id',
        'item_name',
        'stok_sebelum',
        'stok_sesudah',
        'perubahan',
        'aksi',
        'user_name',
        'keterangan',
    ];
}
