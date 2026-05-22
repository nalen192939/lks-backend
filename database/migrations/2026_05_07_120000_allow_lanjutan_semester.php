<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('keterangan')) {
            DB::statement("ALTER TABLE `keterangan` MODIFY `semester` ENUM('1','2','lanjutan') NOT NULL");
        }

        if (Schema::hasTable('tematik')) {
            DB::statement("ALTER TABLE `tematik` MODIFY `semester` ENUM('1','2','lanjutan') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('keterangan')) {
            DB::table('keterangan')->where('semester', 'lanjutan')->update(['semester' => '1']);
            DB::statement("ALTER TABLE `keterangan` MODIFY `semester` ENUM('1','2') NOT NULL");
        }

        if (Schema::hasTable('tematik')) {
            DB::table('tematik')->where('semester', 'lanjutan')->update(['semester' => '1']);
            DB::statement("ALTER TABLE `tematik` MODIFY `semester` ENUM('1','2') NOT NULL");
        }
    }
};
