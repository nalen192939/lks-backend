<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barang')) {
            Schema::create('barang', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('kode_barang', 50);
                $table->string('nama_barang', 255);
                $table->enum('kategori', ['alat_tulis', 'kertas', 'peralatan', 'lainnya'])->default('alat_tulis');
                $table->enum('satuan', ['pcs', 'box', 'pak', 'rim'])->default('pcs');
                $table->integer('stok')->default(0);
                $table->integer('jumlah')->default(0);
                $table->text('keterangan')->nullable();
                $table->string('gambar', 255)->nullable();
                $table->timestamps();
            });

            return;
        }

        if (Schema::hasColumn('barang', 'nomor') && !Schema::hasColumn('barang', 'kode_barang')) {
            DB::statement("ALTER TABLE barang CHANGE nomor kode_barang VARCHAR(50) NOT NULL");
        }

        if (Schema::hasColumn('barang', 'nama') && !Schema::hasColumn('barang', 'nama_barang')) {
            DB::statement("ALTER TABLE barang CHANGE nama nama_barang VARCHAR(255) NOT NULL");
        }

        Schema::table('barang', function (Blueprint $table) {
            if (!Schema::hasColumn('barang', 'kode_barang')) {
                $table->string('kode_barang', 50)->after('id');
            }
            if (!Schema::hasColumn('barang', 'nama_barang')) {
                $table->string('nama_barang', 255)->after('kode_barang');
            }
            if (!Schema::hasColumn('barang', 'kategori')) {
                $table->enum('kategori', ['alat_tulis', 'kertas', 'peralatan', 'lainnya'])->default('alat_tulis')->after('nama_barang');
            }
            if (!Schema::hasColumn('barang', 'satuan')) {
                $table->enum('satuan', ['pcs', 'box', 'pak', 'rim'])->default('pcs')->after('kategori');
            }
            if (!Schema::hasColumn('barang', 'stok')) {
                $table->integer('stok')->default(0)->after('satuan');
            }
            if (!Schema::hasColumn('barang', 'jumlah')) {
                $table->integer('jumlah')->default(0)->after('stok');
            }
            if (!Schema::hasColumn('barang', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('stok');
            }
            if (!Schema::hasColumn('barang', 'gambar')) {
                $table->string('gambar', 255)->nullable()->after('keterangan');
            }
            if (!Schema::hasColumn('barang', 'created_at') && !Schema::hasColumn('barang', 'updated_at')) {
                $table->timestamps();
            }
        });

        DB::statement("ALTER TABLE barang MODIFY kode_barang VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE barang MODIFY nama_barang VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE barang MODIFY kategori ENUM('alat_tulis','kertas','peralatan','lainnya') NOT NULL DEFAULT 'alat_tulis'");
        DB::statement("ALTER TABLE barang MODIFY satuan ENUM('pcs','box','pak','rim') NOT NULL DEFAULT 'pcs'");
        DB::statement("ALTER TABLE barang MODIFY stok INT(11) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE barang MODIFY jumlah INT(11) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE barang MODIFY keterangan TEXT NULL");
        DB::statement("ALTER TABLE barang MODIFY gambar VARCHAR(255) NULL");
        DB::statement("ALTER TABLE barang MODIFY created_at TIMESTAMP NULL DEFAULT NULL");
        DB::statement("ALTER TABLE barang MODIFY updated_at TIMESTAMP NULL DEFAULT NULL");
        DB::statement("ALTER TABLE barang MODIFY id INT(11) NOT NULL AUTO_INCREMENT");

        Schema::table('barang', function (Blueprint $table) {
            if (Schema::hasColumn('barang', 'merk')) {
                $table->dropColumn('merk');
            }
            if (Schema::hasColumn('barang', 'nomor')) {
                $table->dropColumn('nomor');
            }
            if (Schema::hasColumn('barang', 'nama')) {
                $table->dropColumn('nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            if (!Schema::hasColumn('barang', 'nomor')) {
                $table->string('nomor', 50)->nullable()->after('id');
            }
            if (!Schema::hasColumn('barang', 'nama')) {
                $table->string('nama', 255)->nullable()->after('nomor');
            }
            if (!Schema::hasColumn('barang', 'merk')) {
                $table->string('merk', 100)->nullable()->after('nama');
            }
        });
    }
};
