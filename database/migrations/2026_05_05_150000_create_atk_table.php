<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atk')) {
            return;
        }

        Schema::create('atk', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('kode_barang', 50);
            $table->string('barcode', 100)->nullable()->after('kode_barang');
            $table->string('nama_barang', 255);
            $table->enum('kategori', ['alat_tulis', 'kertas', 'peralatan', 'lainnya'])->default('alat_tulis');
            $table->enum('satuan', ['pcs', 'box', 'pak', 'rim'])->default('pcs');
            $table->integer('stok')->default(0);
            $table->string('merk', 100)->nullable();
            $table->integer('jumlah')->default(0);
            $table->text('keterangan')->nullable();
            $table->string('gambar', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atk');
    }
};
