<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keterangan extends Model
{
    protected $table = 'keterangan';
    
    // Matikan timestamps
    public $timestamps = false;
    
    // Primary key adalah id (default Laravel)
    protected $primaryKey = 'id';
    
    // Auto increment
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
        'gambar'
    ];
}
