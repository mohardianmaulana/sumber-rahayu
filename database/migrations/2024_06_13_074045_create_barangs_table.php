<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table-> String('status');
            $table-> integer('kategori_id');
            $table-> String('nama');
            $table-> integer('harga_beli');
            $table-> integer('harga_jual');
            $table-> integer('jumlah');
            $table-> integer('minLimit');
            $table-> integer('maxLimit');
            $table->timestamps();

            $table->foreign('kategori_id')->references('id')->on('kategori')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
