<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function index() 
{
    $today = Carbon::today();

    $penjualan = Penjualan::join('user', 'penjualan.user_id', '=', 'user.id')
        ->leftJoin('customer', 'penjualan.customer_id', '=', 'customer.id') // Join dengan tabel customer
        ->select('penjualan.*', 'user.name as user_nama', 'customer.nama as customer_nama') // Pilih nama customer
        ->whereDate('penjualan.tanggal_transaksi', '=', $today)
        ->orderBy('penjualan.tanggal_transaksi', 'desc')
        ->get();

    // Ambil data customer dari database dengan kondisi status 1
    $customer = Customer::where('status', 1)->get();
    
    return view('penjualan.index', compact('penjualan', 'customer'));
}


public function oldPurchases(Request $request)
{
    $today = Carbon::today();

    $penjualan = Penjualan::join('user', 'penjualan.user_id', '=', 'user.id')
        ->leftJoin('customer', 'penjualan.customer_id', '=', 'customer.id') // Join dengan tabel customer
        ->select('penjualan.*', 'user.name as user_nama', 'customer.nama as customer_nama') // Pilih nama customer
        ->whereDate('penjualan.tanggal_transaksi', '<', $today)
        ->orderBy('penjualan.tanggal_transaksi', 'desc')
        ->get();
    
    return view('penjualan.indexLama', compact('penjualan'));
}

public function create(Request $request)
{
    $barang = Barang::all();
    
    $customer_id = $request->query('customer_id');
    $customer_name = $request->query('customer_name');

    $avgHargaBeli = DB::table('harga_barang')
        ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
        ->whereNull('tanggal_selesai')
        ->groupBy('barang_id')
        ->get();

    // Menyimpan hasil rata-rata ke dalam array
    $rataRataHargaBeli = [];
    foreach ($avgHargaBeli as $avg) {
        $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
    }

    // Ambil data supplier dari database jika diperlukan
    $customer = Customer::find($customer_id);

    return view('penjualan.create', compact('customer', 'barang', 'rataRataHargaBeli'));
}

    public function store(Request $request)
    {
        // Validasi data request
        $request->validate([
            'barang_id' => 'required|array|min:1',
            'barang_id.*' => 'required|exists:barang,id',
            'harga_jual.*' => 'required|numeric|min:1',
            'jumlah.*' => 'required|numeric|min:1',
            'bayar' => 'required|numeric|min:1',
        ], [
            'barang_id.required' => 'Harus memilih setidaknya satu barang',
            'barang_id.*.required' => 'Barang tidak valid',
            'harga_jual.*.required' => 'Harga jual wajib diisi',
            'harga_jual.*.numeric' => 'Harga jual harus berupa angka',
            'harga_jual.*.min' => 'Harga jual tidak boleh kurang dari 1',
            'jumlah.*.required' => 'Jumlah wajib diisi',
            'jumlah.*.numeric' => 'Jumlah harus berupa angka',
            'jumlah.*.min' => 'Jumlah tidak boleh kurang dari 1',
            'bayar.required' => 'Bayar wajib diisi',
            'bayar.numeric' => 'Bayar harus berupa angka',
            'bayar.min' => 'Bayar tidak boleh kurang dari 1',
        ]);

        $totalHarga = 0;
        $totalItem = 0;

        foreach ($request->harga_jual as $index => $harga) {
            $jumlah = $request->jumlah[$index];
            $totalHarga += $harga * $jumlah;
            $totalItem += $jumlah;
        }

        // Ambil nilai bayar, diterima, dan kembali dari request
        $bayar = $request->bayar;
        $kembali = $bayar - $totalHarga;

        // Simpan ke tabel penjualan
        $penjualan = Penjualan::create([
            'customer_id' => $request->customer_id,
            'total_item' => $totalItem,
            'total_harga' => $totalHarga,
            'bayar' => $bayar,
            'kembali' => $kembali,
            'tanggal_transaksi' => now(),
            'user_id' => Auth::id(),
        ]);

        foreach ($request->barang_id as $index => $barang_id) {
            $harga = $request->harga_jual[$index];
            $jumlah = $request->jumlah[$index];
        
            $penjualan->barangs()->attach($barang_id, [
                'jumlah' => $jumlah,
                'harga' => $harga,
                'jumlah_itemporary' => $jumlah
            ]);
        
            $barang = Barang::find($barang_id);
            $barang->jumlah -= $jumlah;
            $barang->save();
        }

        return redirect()->to('penjualan')->with('success', 'Penjualan berhasil disimpan.');
    }

    public function edit($id)
{
    $penjualan = Penjualan::with(['barangs', 'customer', 'user'])->find($id);

    if ($penjualan) {
        $tanggalTransaksi = Carbon::parse($penjualan->tanggal_transaksi);
        $satuBulanLalu = Carbon::now()->subMonth();

        if ($tanggalTransaksi->lt($satuBulanLalu)) {
            return redirect()->route('penjualan.lama')->with('error', 'Penjualan lebih dari satu bulan tidak dapat diedit.');
        }

        $barangs = Barang::all(); // Ambil semua data barang
        return view('penjualan.edit', compact('penjualan', 'barangs'));
    }

    return redirect()->route('penjualan.index')->with('error', 'Penjualan tidak ditemukan.');
}





public function update(Request $request, $id)
{
    $penjualan = Penjualan::find($id);

    if ($penjualan) {
        $tanggalTransaksi = Carbon::parse($penjualan->tanggal_transaksi);
        $satuBulanLalu = Carbon::now()->subMonth();

        if ($tanggalTransaksi->lt($satuBulanLalu)) {
            return redirect()->route('penjualan.lama')->with('error', 'Penjualan lebih dari satu bulan tidak dapat diedit.');
        }

    // Validasi data request
    $request->validate([
        'barang_id' => 'required|array|min:1',
        'barang_id.*' => 'required|exists:barang,id',
        'harga_jual.*' => 'required|numeric|min:1',
        'jumlah.*' => 'required|numeric|min:1',
        'bayar' => 'required|numeric|min:1',
    ], [
        'barang_id.required' => 'Harus memilih setidaknya satu barang',
        'barang_id.*.required' => 'Barang tidak valid',
        'harga_jual.*.required' => 'Harga jual wajib diisi',
        'harga_jual.*.numeric' => 'Harga jual harus berupa angka',
        'harga_jual.*.min' => 'Harga jual tidak boleh kurang dari 1',
        'jumlah.*.required' => 'Jumlah wajib diisi',
        'jumlah.*.numeric' => 'Jumlah harus berupa angka',
        'jumlah.*.min' => 'Jumlah tidak boleh kurang dari 1',
        'bayar.required' => 'Bayar wajib diisi',
        'bayar.numeric' => 'Bayar harus berupa angka',
        'bayar.min' => 'Bayar tidak boleh kurang dari 1',
    ]);

    $totalHarga = 0;
    $totalItem = 0;

    foreach ($request->harga_jual as $index => $harga) {
        $jumlah = $request->jumlah[$index];
        $totalHarga += $harga * $jumlah;
        $totalItem += $jumlah;
    }

    // Ambil nilai bayar, diterima, dan kembali dari request
    $bayar = $request->bayar;
    $kembali = $bayar - $totalHarga;

    // Simpan ke tabel penjualan
    $penjualan->update([
        'total_item' => $totalItem,
        'total_harga' => $totalHarga,
        'bayar' => $bayar,
        'kembali' => $kembali,
        'tanggal_transaksi' => $penjualan->tanggal_transaksi,
        'user_id' => Auth::id(),
    ]);

    // Sinkronisasi data ke tabel pivot
    $syncData = [];
    foreach ($request->barang_id as $index => $barang_id) {
        $harga = $request->harga_jual[$index];
        $jumlah = $request->jumlah[$index];

        // Ambil data lama dari tabel pivot barang_penjualan
        $pivotData = DB::table('barang_penjualan')
            ->where('barang_id', $barang_id)
            ->where('penjualan_id', $penjualan->id)
            ->first();

        // Pastikan pivotData tidak null sebelum mengaksesnya
        $jumlah_itemporary = $pivotData ? $pivotData->jumlah_itemporary : 0;

        $syncData[$barang_id] = [
            'jumlah' => $jumlah,
            'harga' => $harga,
            'jumlah_itemporary' => $jumlah,
        ];

        // Perbarui jumlah barang di tabel barang
        $barang = Barang::find($barang_id);
        if ($pivotData) {
            // Logika untuk jumlah barang
            if ($jumlah < $jumlah_itemporary) {
                $selisihJumlah = $jumlah_itemporary - $jumlah;
                $barang->jumlah += $selisihJumlah;
            } else if ($jumlah > $jumlah_itemporary) {
                $selisihJumlah = $jumlah - $jumlah_itemporary;
                $barang->jumlah -= $selisihJumlah;
            }
        } else {
            $barang->jumlah -= $jumlah;
        }

        // Hanya simpan jika ada perubahan jumlah
        if ($barang->isDirty('jumlah')) {
            $barang->save();
        }
    }

    // Sync data ke tabel pivot
    $penjualan->barangs()->sync($syncData);

    return redirect()->to('penjualan')->with('success', 'Penjualan berhasil diperbarui.');
}

}



public function laporan()
{
    // Get the start and end of the current month
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    $penjualan = DB::table('penjualan')
                ->join('barang_penjualan', 'penjualan.id', '=', 'barang_penjualan.penjualan_id')
                ->join('barang', 'barang_penjualan.barang_id', '=', 'barang.id')
                ->join('customer', 'penjualan.customer_id', '=', 'customer.id')
                ->join('user', 'penjualan.user_id', '=', 'user.id')
                ->select('penjualan.*', 'barang.nama as barang_nama', 
                        'barang_penjualan.jumlah as total_item',
                        'barang_penjualan.harga as total_harga',
                        'customer.nama as nama_customer',
                        'user.name as nama_user')
                ->whereBetween('penjualan.tanggal_transaksi', [$startOfMonth, $endOfMonth])
                ->get();

    return view('penjualan.laporanPenjualan', compact('penjualan'));
}

}