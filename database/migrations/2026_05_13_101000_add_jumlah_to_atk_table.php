<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('atk') || Schema::hasColumn('atk', 'jumlah')) {
            return;
        }

        Schema::table('atk', function (Blueprint $table) {
            $table->integer('jumlah')->default(0)->after('merk');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('atk') || !Schema::hasColumn('atk', 'jumlah')) {
            return;
        }

        Schema::table('atk', function (Blueprint $table) {
            $table->dropColumn('jumlah');
        });
    }
};
