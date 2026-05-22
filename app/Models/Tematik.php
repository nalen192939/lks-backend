<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tematik extends Model
{
    protected $table = 'tematik';
    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'nomor_rak',
        'judul',
        'penerbit',
        'kelas',
        'semester',
        'kurikulum',
        'stok',
        'last_stok_added_at',
        'gambar',
    ];
}
