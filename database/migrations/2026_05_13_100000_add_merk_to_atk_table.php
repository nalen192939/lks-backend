<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('atk') || Schema::hasColumn('atk', 'merk')) {
            return;
        }

        Schema::table('atk', function (Blueprint $table) {
            $table->string('merk', 100)->nullable()->after('stok');
        });

        if (Schema::hasColumn('atk', 'keterangan')) {
            DB::table('atk')
                ->whereNull('merk')
                ->whereNotNull('keterangan')
                ->update(['merk' => DB::raw('LEFT(keterangan, 100)')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('atk') || !Schema::hasColumn('atk', 'merk')) {
            return;
        }

        Schema::table('atk', function (Blueprint $table) {
            $table->dropColumn('merk');
        });
    }
};
