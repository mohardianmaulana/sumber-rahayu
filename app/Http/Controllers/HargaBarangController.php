<?php

namespace App\Http\Controllers;

use App\Models\HargaBarang;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HargaBarangController extends Controller
{
    public function index(Request $request)
{
    // Membuat query join antara tabel 'harga_barang', 'barang', dan 'supplier' dan mengeksekusinya
    $hargaBarang = HargaBarang::leftJoin('barang', 'harga_barang.barang_id', '=', 'barang.id')
        ->leftJoin('supplier', 'harga_barang.supplier_id', '=', 'supplier.id')
        ->select('harga_barang.*', 'barang.nama as nama_barang', 'supplier.nama as nama_supplier')
        ->orderBy('harga_barang.tanggal_mulai', 'asc')
        ->get();

    // Menambahkan flag 'isComplete' untuk setiap item
    $hargaBarang = $hargaBarang->map(function($item) {
        $item->isComplete = !empty($item->nama_barang) && !empty($item->harga_beli) && !empty($item->harga_jual) && !empty($item->tanggal_mulai) && !empty($item->tanggal_selesai);
        
        return $item;
    });

    return view('hargaBarang.index', compact('hargaBarang'));
}
    

    public function edit($id) 
    {
        // Mengambil data harga barang yang ingin diedit dengan join tabel barang untuk mendapatkan nama barang
        $hargaBarang = HargaBarang::join('barang', 'harga_barang.barang_id', '=', 'barang.id')
            ->select('harga_barang.*', 'barang.nama as nama_barang')
            ->where('harga_barang.id', $id)
            ->first();

        // Mengembalikan view dengan data harga barang yang sudah diambil
        return view('hargaBarang.edit', compact('hargaBarang'));
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'harga_jual' => 'required|numeric',
        ]);

        // Mencari data harga barang berdasarkan ID
        $hargaBarang = HargaBarang::findOrFail($id);

        // Memperbarui data harga barang
        $hargaBarang->harga_jual = $request->input('harga_jual');
        
        // Menyimpan perubahan ke database
        $hargaBarang->save();

        // Mengarahkan kembali ke halaman daftar harga dengan pesan sukses
        return redirect()->route('harga')->with('success', 'Harga barang berhasil diperbarui.');
    }
}

