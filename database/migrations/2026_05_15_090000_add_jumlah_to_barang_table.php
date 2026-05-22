<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barang') || Schema::hasColumn('barang', 'jumlah')) {
            return;
        }

        Schema::table('barang', function (Blueprint $table) {
            $table->integer('jumlah')->default(0)->after('stok');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('barang') || !Schema::hasColumn('barang', 'jumlah')) {
            return;
        }

        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('jumlah');
        });
    }
};
