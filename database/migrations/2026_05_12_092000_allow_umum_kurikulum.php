<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['keterangan', 'tematik'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::statement("ALTER TABLE `{$tableName}` MODIFY `kurikulum` ENUM('kurikulum_merdeka','kurikulum_2013','umum') NOT NULL");
            }
        }
    }

    public function down(): void
    {
        foreach (['keterangan', 'tematik'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->where('kurikulum', 'umum')->update(['kurikulum' => 'kurikulum_merdeka']);
                DB::statement("ALTER TABLE `{$tableName}` MODIFY `kurikulum` ENUM('kurikulum_merdeka','kurikulum_2013') NOT NULL");
            }
        }
    }
};
