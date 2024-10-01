<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;
    protected $table = 'penjualan';
    protected $fillable = ['total_item', 'total_harga', 'tanggal_transaksi', 'bayar', 'kembali', 'user_id', 'customer_id'];
    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_penjualan')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary')
                    ->withTimestamps();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function getFormattedTanggalTransaksiAttribute()
    {
        return Carbon::parse($this->attributes['tanggal_transaksi'])->format('d-m-Y');
    }
}
