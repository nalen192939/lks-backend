<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atk extends Model
{
    protected $table = 'atk';

    protected $fillable = [
        'kode_barang',
        'barcode',
        'nama_barang',
        'kategori',
        'satuan',
        'stok',
        'last_stok_added_at',
        'merk',
        'jumlah',
        'keterangan',
        'gambar',
    ];
}
