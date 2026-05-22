<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 30);
            $table->unsignedBigInteger('item_id');
            $table->string('item_name')->nullable();
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
            $table->integer('perubahan');
            $table->string('aksi', 20);
            $table->string('user_name')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
