<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['keterangan', 'tematik', 'atk', 'barang'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'last_stok_added_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('last_stok_added_at')->nullable()->after('stok');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['keterangan', 'tematik', 'atk', 'barang'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'last_stok_added_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('last_stok_added_at');
                });
            }
        }
    }
};
