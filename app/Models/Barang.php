<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'stok',
        'jumlah',
        'last_stok_added_at',
        'keterangan',
        'gambar',
    ];
}
