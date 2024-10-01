<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Barang extends Model
{
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
    use HasFactory;
    protected $table = 'barang';
    protected $fillable = ['kategori_id', 'nama', 'harga_jual', 'jumlah', 'minLimit', 'maxLimit', 'status'];
    public function pembelians()
    {
        return $this->belongsToMany(Pembelian::class, 'barang_pembelian')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary', 'harga_itemporary');
    }
    public function penjualans()
    {
        return $this->belongsToMany(Penjualan::class, 'barang_penjualan')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary');
    }

}
