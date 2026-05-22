<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('keterangan')) {
            return;
        }

        Schema::create('keterangan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_rak', 20);
            $table->string('judul', 255);
            $table->string('penerbit', 100);
            $table->string('kelas', 10);
            $table->enum('semester', ['1', '2', 'lanjutan']);
            $table->enum('kurikulum', ['kurikulum_merdeka', 'kurikulum_2013', 'umum']);
            $table->unsignedInteger('stok')->default(0);
            $table->timestamp('last_stok_added_at')->nullable();
            $table->string('gambar', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keterangan');
    }
};
