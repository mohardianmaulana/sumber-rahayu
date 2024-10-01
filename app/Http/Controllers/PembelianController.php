<?php

namespace App\Http\Controllers;
use App\Models\Pembelian;
use App\Models\Supplier;
use App\Models\Barang;
use App\Models\HargaBarang;
use App\Models\Kategori;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PembelianController extends Controller
{
    public function index(Request $request)
    {
        $satuBulanLalu = Carbon::now()->subMonth();

        $pembelian = Pembelian::join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
            ->join('user', 'pembelian.user_id', '=', 'user.id')
            ->select('pembelian.*', 'supplier.nama as supplier_nama', 'user.name as user_nama')
            ->whereDate('pembelian.created_at', '>=', $satuBulanLalu)
            ->orderBy('pembelian.created_at', 'desc')
            ->get();

        // Ambil data supplier dari database dengan kondisi status 1
        $supplier = Supplier::where('status', 1)->get();
        $user = User::all();
        
        return view('pembelian.index', compact('pembelian', 'supplier', 'user'));
    }

    public function oldPurchases(Request $request)
{
    $satuBulanLalu = Carbon::now()->subMonth();

    $pembelian = Pembelian::join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
        ->join('user', 'pembelian.user_id', '=', 'user.id')
        ->select('pembelian.*', 'supplier.nama as supplier_nama', 'user.name as user_nama')
        ->whereDate('pembelian.created_at', '<=', $satuBulanLalu)
        ->orderBy('pembelian.created_at', 'desc')
        ->get();

    $supplier = Supplier::all();
    $user = User::all();
    
    return view('pembelian.indexLama', compact('pembelian', 'supplier', 'user'));
}


    public function create(Request $request)
    {
        $barang = Barang::all();
        
        $supplier_id = $request->query('supplier_id');
        $supplier_name = $request->query('supplier_name');

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
        $supplier = Supplier::find($supplier_id);

        return view('pembelian.create', compact('supplier', 'barang', 'rataRataHargaBeli'));
    }

    public function store(Request $request)
    {
        // Validasi data request
        $request->validate([
            'barang_id' => 'required|array|min:1',
            'barang_id.*' => 'required|exists:barang,id',
            'harga_beli.*' => 'required|numeric|min:1',
            'jumlah.*' => 'required|numeric|min:1',
        ], [
            'barang_id.required' => 'Harus memilih setidaknya satu barang',
            'barang_id.*.required' => 'Barang tidak valid',
            'harga_beli.*.required' => 'Harga beli wajib diisi',
            'harga_beli.*.numeric' => 'Harga beli harus berupa angka',
            'harga_beli.*.min' => 'Harga beli tidak boleh kurang dari 1',
            'jumlah.*.required' => 'Jumlah wajib diisi',
            'jumlah.*.numeric' => 'Jumlah harus berupa angka',
            'jumlah.*.min' => 'Jumlah tidak boleh kurang dari 1',
        ]);
    
        $totalHarga = 0;
        $totalItem = 0;
    
        foreach ($request->harga_beli as $index => $harga) {
            $jumlah = $request->jumlah[$index];
            $totalHarga += $harga * $jumlah;
            $totalItem += $jumlah;
        }
    
        // Simpan ke tabel pembelian
        $pembelian = Pembelian::create([
            'supplier_id' => $request->supplier_id,
            'total_item' => $totalItem,
            'total_harga' => $totalHarga,
            'tanggal_transaksi' => now(),
            'user_id' => Auth::id(),
        ]);
    
        foreach ($request->barang_id as $index => $barang_id) {
            $harga = $request->harga_beli[$index];
            $jumlah = $request->jumlah[$index];
        
            $pembelian->barangs()->attach($barang_id, [
                'jumlah' => $jumlah,
                'harga' => $harga,
                'jumlah_itemporary' => $jumlah,
            ]);
        
            $barang = Barang::find($barang_id);
            $barang->jumlah += $jumlah;
            $barang->save();
    
            // Periksa dan perbarui harga_barang
            $hargaBarang = HargaBarang::where('barang_id', $barang_id)
            ->where('supplier_id', $request->supplier_id)
            ->whereNull('tanggal_selesai')
            ->first();
    
            if ($hargaBarang) {
                if ($hargaBarang->harga_beli != $harga) {
                    $hargaBarang->tanggal_selesai = now();
                    $hargaBarang->save();
    
                    // Buat baris baru dengan harga dan supplier baru
                    HargaBarang::create([
                        'barang_id' => $barang_id,
                        'harga_beli' => $harga,
                        'supplier_id' => $request->supplier_id, // Simpan supplier_id
                        'tanggal_mulai' => now(),
                        'tanggal_selesai' => null,
                    ]);
                }
            } else {
                // Jika tidak ada harga sebelumnya, buat baris baru
                HargaBarang::create([
                    'barang_id' => $barang_id,
                    'harga_beli' => $harga,
                    'supplier_id' => $request->supplier_id, // Simpan supplier_id
                    'tanggal_mulai' => now(),
                    'tanggal_selesai' => null,
                ]);
            }
        }
    
        return redirect()->to('pembelian')->with('success', 'Pembelian berhasil disimpan.');
    }
    

    public function edit($id)
{
    $pembelian = Pembelian::with(['barangs', 'supplier', 'user'])->find($id);

    if ($pembelian) {
        $tanggalTransaksi = Carbon::parse($pembelian->tanggal_transaksi);
        $satuBulanLalu = Carbon::now()->subMonth();

        if ($tanggalTransaksi->lt($satuBulanLalu)) {
            return redirect()->route('pembelian.lama')->with('error', 'Penjualan lebih dari satu bulan tidak dapat diedit.');
        }

        $avgHargaBeli = DB::table('harga_barang')
            ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
            ->whereNull('tanggal_selesai')
            ->groupBy('barang_id')
            ->get();

        $rataRataHargaBeli = [];
        foreach ($avgHargaBeli as $avg) {
            $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
        }

        $tanggalTransaksi = $pembelian->tanggal_transaksi->format('d-m-Y');
        $barangs = Barang::all(); // Ambil semua data barang

        return view('pembelian.edit', compact('pembelian', 'barangs', 'rataRataHargaBeli'));
    }

    return redirect()->route('pembelian.index')->with('error', 'Pembelian tidak ditemukan.');
}



public function update(Request $request, $id)
{

    $pembelian = Pembelian::find($id);

    $tanggalTransaksi = Carbon::parse($pembelian->tanggal_transaksi);
    $satuBulanLalu = Carbon::now()->subMonth();

    if ($tanggalTransaksi->lt($satuBulanLalu)) {
        return redirect()->route('pembelian.lama')->with('error', 'Penjualan lebih dari satu bulan tidak dapat diedit.');
    }

    // Validasi data request
    $request->validate([
        'barang_id' => 'required|array|min:1',
        'barang_id.*' => 'required|exists:barang,id',
        'harga_beli.*' => 'required|numeric|min:1',
        'jumlah.*' => 'required|numeric|min:1',
    ], [
        'barang_id.required' => 'Harus memilih setidaknya satu barang',
        'barang_id.*.required' => 'Barang tidak valid',
        'harga_beli.*.required' => 'Harga beli wajib diisi',
        'harga_beli.*.numeric' => 'Harga beli harus berupa angka',
        'harga_beli.*.min' => 'Harga beli tidak boleh kurang dari 1',
        'jumlah.*.required' => 'Jumlah wajib diisi',
        'jumlah.*.numeric' => 'Jumlah harus berupa angka',
        'jumlah.*.min' => 'Jumlah tidak boleh kurang dari 1',
    ]);

    $totalHarga = 0;
    $totalItem = 0;

    foreach ($request->harga_beli as $index => $harga) {
        $jumlah = $request->jumlah[$index];
        $totalHarga += $harga * $jumlah;
        $totalItem += $jumlah;
    }

    // Simpan ke tabel pembelian
    $pembelian->update([
        'supplier_id' => $pembelian->supplier_id,
        'total_item' => $totalItem,
        'total_harga' => $totalHarga,
        'tanggal_transaksi' => $pembelian->tanggal_transaksi,
        'user_id' => Auth::id(),
    ]);

    // Sinkronisasi data ke tabel pivot
    $syncData = [];
    foreach ($request->barang_id as $index => $barang_id) {
        $harga = $request->harga_beli[$index];
        $jumlah = $request->jumlah[$index];

        // Logging
        Log::info('Barang ID: ' . $barang_id);
        Log::info('Harga Beli: ' . $harga);
        Log::info('Jumlah: ' . $jumlah);

        // Ambil data lama dari tabel pivot barang_pembelian
        $pivotData = DB::table('barang_pembelian')
            ->where('barang_id', $barang_id)
            ->where('pembelian_id', $pembelian->id)
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
                $barang->jumlah -= $selisihJumlah;
            } else if ($jumlah > $jumlah_itemporary) {
                $selisihJumlah = $jumlah - $jumlah_itemporary;
                $barang->jumlah += $selisihJumlah;
            }
        } else {
            // Jika tidak ada data sebelumnya, langsung tambahkan jumlah
            $barang->jumlah += $jumlah;
        }

        // Hanya simpan jika ada perubahan jumlah
        if ($barang->isDirty('jumlah')) {
            $barang->save();
        }

        $supplier_id = $request->input('supplier_id', $pembelian->supplier_id);

        // Perbarui harga barang di tabel harga_barang
        $hargaBarang = HargaBarang::where('barang_id', $barang_id)
            ->where('supplier_id', $supplier_id)
            ->whereNull('tanggal_selesai')
            ->first();

        // dd($hargaBarang);
            if ($hargaBarang) {
                // Logika untuk harga barang
                if ($harga != $hargaBarang->harga_beli) {
                    $hargaBarang->update([
                        'harga_beli' => $harga,
                    ]);
                }
        }
    }

    // Sync data ke tabel pivot
    $pembelian->barangs()->sync($syncData);

    return redirect()->to('pembelian')->with('success', 'Pembelian berhasil diperbarui.');
}

public function laporan()
    {
        $pembelian = DB::table('pembelian')
                    ->join('barang_pembelian', 'pembelian.id', '=', 'barang_pembelian.pembelian_id')
                    ->join('barang', 'barang_pembelian.barang_id', '=', 'barang.id')
                    ->join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
                    ->select('pembelian.*', 'barang.nama as barang_nama',
                             'supplier.nama as nama_supplier',
                             'barang_pembelian.harga as harga' )
                    ->get();

        return view('pembelian.laporanpembelian', compact('pembelian'));
    }

}
