<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBarangPembelianTable extends Migration
{
    public function up()
    {
        Schema::create('barang_pembelian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_id');
            $table->unsignedBigInteger('pembelian_id');
            $table->integer('jumlah');
            $table->integer('jumlah_itemporary');
            $table->decimal('harga', 15, 2);
            $table->timestamps();

            $table->foreign('barang_id')->references('id')->on('barang')->onDelete('cascade');
            $table->foreign('pembelian_id')->references('id')->on('pembelian')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('barang_pembelian');
    }
}
