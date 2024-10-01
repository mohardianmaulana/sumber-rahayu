<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pembelian extends Model
{
    use HasFactory;
    protected $table = 'pembelian';
    protected $fillable = ['supplier_id', 'total_item', 'total_harga', 'tanggal_transaksi', 'user_id'];
    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_pembelian')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getFormattedTanggalTransaksiAttribute()
    {
        return Carbon::parse($this->attributes['tanggal_transaksi'])->format('d-m-Y');
    }
    protected $casts = [
        'tanggal_transaksi' => 'date',
    ];
}
