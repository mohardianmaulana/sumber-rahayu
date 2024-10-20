<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Supplier;
use App\Models\Barang;
use App\Models\HargaBarang;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PembelianBarangBaruController extends Controller
{
    public function scanPage()
    {
        return view('scan');
    }

    // Memproses hasil scan
    public function processScan(Request $request)
    {
        // Validasi data dari hasil scan QR Code
        $validatedData = $request->validate([
            'qr_result' => 'required', // Atur field sesuai kebutuhan
        ]);

        // Jika valid, redirect ke halaman create_barang
        return redirect()->route('create_barang');
    }
    public function create() 
    {
        $supplier = Supplier::where('status', 1)->get();
        $kategori = Kategori::where('status', 1)->get();
        return view('pembelian.create_barang', compact('kategori', 'supplier'));
    }

    public function store(Request $request) 
{
    $validator = Validator::make($request->all(), [
        'nama' => 'required',
        'jumlah' => 'required|numeric|min:0',
        'harga_beli' => 'required|numeric|min:1',
        'harga_jual' => 'required|numeric|min:1',
        'minLimit' => 'required|numeric|min:1',
        'maxLimit' => [
            'required', 
            'numeric',
            'min:1',
            function ($attribute, $value, $fail) use ($request) {
                if ($value < $request->minLimit) {
                    $fail('Max Limit tidak boleh lebih kecil daripada Min Limit');
                }
            }
        ],
        'kategori_id' => 'required',
        'supplier_id' => 'required'
    ], [
        'nama.required' => 'Nama Barang wajib diisi',
        'jumlah.required' => 'Jumlah wajib diisi',
        'jumlah.min' => 'Jumlah tidak boleh kurang dari 0',
        'harga_beli.required' => 'Harga beli wajib diisi',
        'harga_beli.min' => 'Harga beli tidak boleh kurang dari 0',
        'harga_jual.required' => 'Harga jual wajib diisi',
        'harga_jual.min' => 'Harga jual tidak boleh kurang dari 0',
        'minLimit.required' => 'Min Limit wajib diisi',
        'minLimit.min' => 'Min Limit tidak boleh kurang dari 0',
        'maxLimit.required' => 'Max Limit wajib diisi',
        'maxLimit.min' => 'Max Limit tidak boleh kurang dari 0',
        'kategori_id.required' => 'Kategori wajib diisi',
        'supplier_id.required' => 'Supplier wajib diisi',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
    }

    // Simpan ke tabel barang
    $barang = Barang::create([
        'nama' => $request->nama,
        'jumlah' => $request->jumlah,  // Jangan menambahkan jumlah di sini, biarkan sebagai jumlah input
        'harga_jual' => $request->harga_jual,
        'minLimit' => $request->minLimit,
        'maxLimit' => $request->maxLimit,
        'kategori_id' => $request->kategori_id,
        'status' => 1,
    ]);

    // Hitung total harga
    $harga_beli = $request->harga_beli;
    $jumlah = $request->jumlah;
    $totalHarga = $harga_beli * $jumlah;

    // Simpan ke tabel pembelian
    $pembelian = Pembelian::create([
        'supplier_id' => $request->supplier_id,
        'total_item' => $jumlah,
        'total_harga' => $totalHarga,
        'tanggal_transaksi' => now(),
        'user_id' => Auth::id(),
    ]);

    // Simpan ke tabel pivot barang_pembelian
    $pembelian->barangs()->attach($barang->id, [
        'jumlah' => $jumlah, 
        'harga' => $harga_beli, 
        'jumlah_itemporary' => $jumlah, 
    ]);

    // Perbarui stok barang
    $barang->jumlah = $jumlah;  // Menggunakan jumlah input asli
    $barang->save();

    // Periksa dan perbarui harga_barang
    $hargaBarang = HargaBarang::where('barang_id', $barang->id)
        ->where('supplier_id', $request->supplier_id)
        ->whereNull('tanggal_selesai')
        ->first();

    if ($hargaBarang) {
        if ($hargaBarang->harga_beli != $harga_beli) {
            $hargaBarang->tanggal_selesai = now();
            $hargaBarang->save();

            // Buat baris baru dengan harga dan supplier baru
            HargaBarang::create([
                'barang_id' => $barang->id,
                'harga_beli' => $harga_beli,
                'harga_jual' => $request->harga_jual,
                'supplier_id' => $request->supplier_id,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => null,
            ]);
        }
    } else {
        HargaBarang::create([
            'barang_id' => $barang->id,
            'harga_beli' => $harga_beli,
            'harga_jual' => $request->harga_jual,
            'supplier_id' => $request->supplier_id,
            'tanggal_mulai' => now(),
            'tanggal_selesai' => null,
        ]);
    }

    return redirect()->to('pembelian')->with('success', 'Produk berhasil ditambahkan');
}

}
