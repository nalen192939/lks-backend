<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atk', function (Blueprint $table) {
            if (!Schema::hasColumn('atk', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('kode_barang');
            }
        });
    }

    public function down(): void
    {
        Schema::table('atk', function (Blueprint $table) {
            if (Schema::hasColumn('atk', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
    }
};
