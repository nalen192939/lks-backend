<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 50);
            $table->string('nama', 255);
            $table->string('merk', 100);
            $table->integer('jumlah')->default(0);
            $table->string('gambar', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
