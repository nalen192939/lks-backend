<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $kelasMap = [
        '1' => 'I',
        '2' => 'II',
        '3' => 'III',
        '4' => 'IV',
        '5' => 'V',
        '6' => 'VI',
        '7' => 'VII',
        '8' => 'VIII',
        '9' => 'IX',
        '10' => 'X',
        '11' => 'XI',
        '12' => 'XII',
    ];

    public function up(): void
    {
        foreach (['keterangan', 'tematik'] as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'kelas')) {
                continue;
            }

            foreach ($this->kelasMap as $number => $roman) {
                DB::update("UPDATE `{$tableName}` SET `kelas` = ? WHERE CAST(`kelas` AS CHAR) = ?", [$roman, (string) $number]);
            }
        }
    }

    public function down(): void
    {
        foreach (['keterangan', 'tematik'] as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'kelas')) {
                continue;
            }

            foreach ($this->kelasMap as $number => $roman) {
                DB::update("UPDATE `{$tableName}` SET `kelas` = ? WHERE CAST(`kelas` AS CHAR) = ?", [(string) $number, $roman]);
            }
        }
    }
};
