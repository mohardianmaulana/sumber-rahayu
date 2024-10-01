<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian_detail extends Model
{
    use HasFactory;
    protected $table = 'pembelian_detail';
    protected $fillable = ['barang_id', 'pembelian_id', 'harga_beli', 'jumlah', 'subtotal'];
}
