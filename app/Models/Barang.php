<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $fillable = ['kategori_id', 'nama', 'harga_jual', 'jumlah', 'minLimit', 'maxLimit', 'status'];

    // Relasi dengan Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Relasi dengan Pembelian
    public function pembelians()
    {
        return $this->belongsToMany(Pembelian::class, 'barang_pembelian')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary', 'harga_itemporary');
    }

    // Relasi dengan Penjualan
    public function penjualans()
    {
        return $this->belongsToMany(Penjualan::class, 'barang_penjualan')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary');
    }

    // Method untuk mendapatkan data barang, kategori, dan harga terbaru
    public static function getAllBarangWithKategoriAndHarga()
    {
        // Membuat subquery untuk mendapatkan harga terbaru dari tabel harga_barang
        $subquery = DB::table('harga_barang')
            ->select('barang_id', DB::raw('MAX(tanggal_mulai) as max_tanggal_mulai'))
            ->groupBy('barang_id');

        // Membuat query join antara tabel 'barang', 'kategori', dan 'harga_barang' menggunakan subquery
        return self::join('kategori', 'barang.kategori_id', '=', 'kategori.id')
            ->joinSub($subquery, 'hb_latest', function($join) {
                $join->on('barang.id', '=', 'hb_latest.barang_id');
            })
            ->join('harga_barang', function($join) {
                $join->on('hb_latest.barang_id', '=', 'harga_barang.barang_id')
                     ->on('hb_latest.max_tanggal_mulai', '=', 'harga_barang.tanggal_mulai');
            })
            ->where('barang.status', 1) // Hanya barang dengan status aktif
            ->select(
                'barang.id', 
                'barang.nama', 
                'barang.kategori_id', 
                'kategori.nama_kategori as kategori_nama', 
                DB::raw('MIN(harga_barang.harga_beli) as harga_beli'), 
                'harga_barang.harga_jual', 
                'barang.jumlah', 
                'barang.minLimit', 
                'barang.maxLimit'
            )
            ->groupBy(
                'barang.id', 
                'barang.nama', 
                'barang.kategori_id', 
                'kategori.nama_kategori', 
                'harga_barang.harga_jual', 
                'barang.jumlah', 
                'barang.minLimit', 
                'barang.maxLimit'
            )
            ->get();
    }

    // Method untuk menghitung rata-rata harga beli
    public static function getAverageHargaBeli()
    {
        $avgHargaBeli = DB::table('harga_barang')
            ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
            ->whereNull('tanggal_selesai')
            ->groupBy('barang_id')
            ->get();

        // Mengubah hasil menjadi array untuk memudahkan akses
        $rataRataHargaBeli = [];
        foreach ($avgHargaBeli as $avg) {
            $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
        }

        return $rataRataHargaBeli;
    }
}
