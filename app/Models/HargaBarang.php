<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaBarang extends Model
{
    use HasFactory;
    protected $table = 'harga_barang';
    protected $fillable = [
        'barang_id', 'supplier_id', 'harga_beli', 'harga_jual', 'tanggal_mulai', 'tanggal_selesai'
    ];
    // Memformat tanggal mulai
    public function getFormattedTanggalMulaiAttribute()
    {
        return Carbon::parse($this->attributes['tanggal_mulai'])->format('d-m-Y');
    }

    public function getFormattedTanggalSelesaiAttribute()
    {
    return $this->attributes['tanggal_selesai'] ? Carbon::parse($this->attributes['tanggal_selesai'])->format('d-m-Y') : null;
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
