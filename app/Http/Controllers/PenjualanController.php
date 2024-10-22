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
    // Memanggil method dari model Penjualan
    $data = Penjualan::tampil();

    // Mengirim data penjualan dan customer ke view
    return view('penjualan.index', [
        'penjualan' => $data['penjualan'],
        'customer' => $data['customers'],
    ]);
}


public function oldPurchases(Request $request)
{
    $penjualan = Penjualan::tampilLama();
    
    return view('penjualan.indexLama', compact('penjualan'));
}

public function create(Request $request)
{
    // Ambil customer_id dari query parameter
    $customer_id = $request->query('customer_id');

    // Panggil method `prepareCreateData` dari model Penjualan
    $data = Penjualan::tambah($customer_id);

    // Mengirim data ke view
    return view('penjualan.create', [
        'customer' => $data['customer'],
        'barang' => $data['barang'],
        'rataRataHargaBeli' => $data['rataRataHargaBeli'],
    ]);
}

    public function store(Request $request)
    {
        // Panggil method `storePenjualan` dari model
    $result = Penjualan::tambahPenjualan($request);

    // Jika terjadi error validasi, kembalikan dengan error
    if ($result['status'] == 'error') {
        return redirect()->back()
                         ->withErrors($result['errors'])
                         ->withInput();
    }

    // Jika berhasil, kembalikan dengan pesan sukses
    return redirect()->to('penjualan')->with('success', 'Penjualan berhasil disimpan.');
    }

    public function edit($id)
{
    // Panggil method `getPenjualanForEdit` dari model
    $penjualan = Penjualan::edit($id);

    // Jika terjadi error (penjualan tidak ditemukan atau lebih dari 1 bulan)
    if ($penjualan['status'] == 'error') {
        $route = isset($penjualan['redirect_route']) ? $penjualan['redirect_route'] : 'penjualan.index';
        return redirect()->route($route)->with('error', $penjualan['message']);
    }

    // Ambil data penjualan dan barangs dari hasil
    $penjualan = $penjualan['penjualan'];
    $barangs = $penjualan['barangs'];

    // Jika berhasil, arahkan ke view edit
    return view('penjualan.edit', compact('penjualan', 'barangs'));
}

public function update(Request $request, $id)
{
    // Panggil method `updatePenjualan` dari model
    $result = Penjualan::updatePenjualan($request, $id);

    // Jika terjadi error, arahkan sesuai dengan kondisi
    if ($result['status'] == 'error') {
        $route = isset($result['redirect_route']) ? $result['redirect_route'] : 'penjualan.index';
        return redirect()->route($route)->with('error', $result['message'] ?? 'Terjadi kesalahan.');
    }

    // Jika berhasil, arahkan ke halaman index dengan pesan sukses
    return redirect()->to('penjualan')->with('success', $result['message']);
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